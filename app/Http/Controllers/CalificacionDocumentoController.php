<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CalificacionesExport;

class CalificacionDocumentoController extends Controller
{
    public function index(Request $request)
    {
        // 1) Progreso del cuatri (0..1) y etapa/tutorías (1..3)
        $progresoCuatri = $this->progresoCuatri();     // float 0..1
        $tutoriaEtapa   = $this->unidadIndexFor(3);    // 1..3

        // 2) Bloques de tipos
        $tiposEstandar = [
            'Reporte de Evaluación Continua por Unidad de Aprendizaje (SIGO)',
            'Informe de Estudiantes No Acreditados',
            'Control de Asesorías',
            'Seguimiento de la Planeación',
        ];
        $especiales = [
            'Presentación de la Asignatura',
            'Planeación didáctica',
        ];
        $generalesTutor = [
            'Presentación del Tutor',
            '1er Tutoría Grupal',
            '2da Tutoría Grupal',
            '3er Tutoría Grupal',
            'Registro de Proyecto Institucional',
            'Informe Parcial',
            'Informe Global',
        ];
        // Habilitación SOLO para las 3 tutorías por etapa
        $unidadRequeridaPorTipo = [
            '1er Tutoría Grupal' => 1,
            '2da Tutoría Grupal' => 2,
            '3er Tutoría Grupal' => 3,
        ];

        // 3) Profes con carga
        $teacherIdsConCarga = DB::connection('cargahoraria')
            ->table('teacher_subjects')
            ->pluck('teacher_id')
            ->unique();

        $profesores = User::whereNotNull('teacher_id')
            ->whereIn('teacher_id', $teacherIdsConCarga)
            ->orderBy('nombres')
            ->get(['id','nombres','teacher_id','categoria']);

        $teacherIdsBase = $profesores->pluck('teacher_id')->all();
        $userIdsBase    = $profesores->pluck('id')->all();

        // 4) Materias por profe (esperados de docs “normales” = #materias)
        $materiasPorTeacher = DB::connection('cargahoraria')
            ->table('teacher_subjects')
            ->select('teacher_id', DB::raw('COUNT(DISTINCT CONCAT(subject_id,"-",group_id)) as total_materias'))
            ->whereIn('teacher_id', $teacherIdsBase)
            ->groupBy('teacher_id')
            ->pluck('total_materias','teacher_id');

        // 5) Unidades por materia (para calcular UNIDAD VIGENTE por profesor)
        //    ts: teacher_subjects, s: subjects (s.unidades)
        $unidadesPorTeacher = DB::connection('cargahoraria')
            ->table('teacher_subjects as ts')
            ->join('subjects as s', 's.subject_id','=','ts.subject_id')
            ->whereIn('ts.teacher_id', $teacherIdsBase)
            ->select('ts.teacher_id','s.unidades')
            ->get()
            ->groupBy('teacher_id')
            ->map(function($rows) use ($progresoCuatri) {
                $actuales = [];
                foreach ($rows as $r) {
                    $n = max(1, (int)$r->unidades);
                    $actuales[] = $this->unidadIndexFor($n, $progresoCuatri);
                }
                // Mediana por profe (más estable que promedio)
                sort($actuales);
                $cnt = count($actuales);
                if ($cnt === 0) return 1;
                $mid = intdiv($cnt, 2);
                if ($cnt % 2 === 0) {
                    // promedio de los dos del centro
                    return (int) round(($actuales[$mid-1] + $actuales[$mid]) / 2);
                }
                return (int) $actuales[$mid];
            });

        // 6) Promedio por documento normal (por documento_id)
        $avgPorDocumento = DB::table('calificacion_documentos')
            ->select('documento_id', DB::raw('AVG(calificacion) as prom_item'))
            ->groupBy('documento_id');

        $rowsDocs = DB::table('documentos_subidos as ds')
            ->leftJoinSub($avgPorDocumento,'pdoc','pdoc.documento_id','=','ds.id')
            ->whereIn('ds.user_id',$userIdsBase)
            ->select('ds.id','ds.user_id','ds.unidad','ds.tipo_documento','pdoc.prom_item')
            ->get();

        // 7) Tutorías con calificaciones (agrupadas por profesor + submódulo)
        $rowsSubs = DB::table('submodulo_archivos as sa')
            ->join('submodulos as sm','sm.id','=','sa.submodulo_id')
            ->join('subsections as ss','ss.id','=','sm.subsection_id')
            ->leftJoin('calificacion_submodulo_archivos as csa','csa.submodulo_archivo_id','=','sa.id')
            ->where('ss.modulo_id',5) // módulo 5 = Tutorías
            ->whereIn('sa.user_id',$userIdsBase)
            ->select(
                'sa.user_id',                      // dueño del archivo = profesor
                'sm.titulo as submodulo_titulo',   // título del submódulo
                DB::raw('COUNT(sa.id) as entregados'),
                DB::raw('AVG(csa.calificacion) as prom_item') // promedio real por profe/submódulo
            )
            ->groupBy('sa.user_id','sm.titulo')
            ->get();

        // 8) Agrupar ENTREGADOS / PROMEDIOS
        $entPorTipo  = []; // [user_id][tipo] = count
        $promPorTipo = []; // [user_id][tipo] = ['suma'=>..., 'n'=>...]

        // Documentos normales (no tutorías) — NO se gatean por unidad
        foreach ($rowsDocs as $row) {
            $uid  = (int)$row->user_id;
            $tipo = trim($row->tipo_documento ?? '');
            if ($tipo === '') continue;

            $entPorTipo[$uid][$tipo] = ($entPorTipo[$uid][$tipo] ?? 0) + 1;

            if (!is_null($row->prom_item)) {
                if (!isset($promPorTipo[$uid][$tipo])) {
                    $promPorTipo[$uid][$tipo] = ['suma'=>0.0,'n'=>0];
                }
                $promPorTipo[$uid][$tipo]['suma'] += (float)$row->prom_item;
                $promPorTipo[$uid][$tipo]['n']    += 1;
            }
        }

        // Tutorías desde submódulos (presentación/1a/2a/3a/proyecto/parcial/global)
        foreach ($rowsSubs as $row) {
            $uid  = (int)$row->user_id;
            $tipo = $this->mapearTutorias($row->submodulo_titulo);
            if (!$tipo) continue;

            // Gating SOLO para las 3 tutorías: se compara vs. $tutoriaEtapa (1..3)
            $permitido = true;
            if (isset($unidadRequeridaPorTipo[$tipo])) {
                $req = $unidadRequeridaPorTipo[$tipo];
                $permitido = ($tutoriaEtapa >= $req);
            }
            if (!$permitido) continue;

            // Entregados: ya viene agrupado por submódulo (por profe)
            $entPorTipo[$uid][$tipo] = ((int)($entPorTipo[$uid][$tipo] ?? 0)) + (int)$row->entregados;

            // Promedio: AVG por profe/submódulo; si hay varios submódulos del mismo tipo, se promedian entre sí
            if (!is_null($row->prom_item)) {
                if (!isset($promPorTipo[$uid][$tipo])) {
                    $promPorTipo[$uid][$tipo] = ['suma'=>0.0,'n'=>0];
                }
                $promPorTipo[$uid][$tipo]['suma'] += (float)$row->prom_item;
                $promPorTipo[$uid][$tipo]['n']    += 1;
            }
        }

        // 9) Armar salida por profesor
        $resumenPorDocumento = [];
        foreach ($profesores as $p) {
            $uid = (int)$p->id;
            $tid = (int)$p->teacher_id;

            $totalMaterias = (int)($materiasPorTeacher[$tid] ?? 0);
            $unidadVigenteProfe = (int)($unidadesPorTeacher[$tid] ?? 1); // mediana entre sus materias

            $detallesTipos = [];

            // Especiales + Estándar (esperados = #materias)
            foreach (array_merge($especiales, $tiposEstandar) as $tipo) {
                $esperados  = $totalMaterias;
                $entregados = (int)($entPorTipo[$uid][$tipo] ?? 0);
                if ($esperados > 0) $entregados = min($entregados, $esperados); else $entregados = 0;

                $sumaN = $promPorTipo[$uid][$tipo]['suma'] ?? 0.0;
                $nN    = $promPorTipo[$uid][$tipo]['n']    ?? 0;
                $prom  = $nN > 0 ? round($sumaN / $nN, 2) : null;
                $cumpl = ($esperados > 0) ? (int)round(($entregados / $esperados) * 100) : null;

                $detallesTipos[] = [
                    'tipo'         => $tipo,
                    'esperados'    => $esperados,
                    'entregados'   => $entregados,
                    'cumplimiento' => $cumpl,
                    'promedio'     => $prom,
                ];
            }

            // Tutorías (esperados = 1 si ya está habilitada esa etapa por progreso)
            foreach ($generalesTutor as $tipo) {
                $req        = $unidadRequeridaPorTipo[$tipo] ?? null;
                $habilitado = is_null($req) || ($tutoriaEtapa >= $req);
                $esperados  = $habilitado ? 1 : 0;

                $entregados = (int)($entPorTipo[$uid][$tipo] ?? 0);
                if ($esperados > 0) $entregados = min($entregados, $esperados); else $entregados = 0;

                $sumaN = $promPorTipo[$uid][$tipo]['suma'] ?? 0.0;
                $nN    = $promPorTipo[$uid][$tipo]['n']    ?? 0;
                $prom  = $nN > 0 ? round($sumaN / $nN, 2) : null;
                $cumpl = ($esperados > 0) ? (int)round(($entregados / $esperados) * 100) : null;

                $detallesTipos[] = [
                    'tipo'         => $tipo,
                    'esperados'    => $esperados,
                    'entregados'   => $entregados,
                    'cumplimiento' => $cumpl,
                    'promedio'     => $prom,
                ];
            }

            $resumenPorDocumento[] = [
                'user_id'      => $uid,
                'nombre'       => $p->nombres,
                'teacher_id'   => $tid,
                'categoria'    => $p->categoria,
                // ✅ Unidad vigente calculada por PROFESOR (mediana entre sus materias)
                'unidad_hasta' => $unidadVigenteProfe,
                'docs'         => $detallesTipos,
            ];
        }

        usort($resumenPorDocumento, fn($a,$b) => strcmp($a['nombre'], $b['nombre']));

        return view('settings.calificaciones.index', [
            'resumenPorDocumento' => $resumenPorDocumento,
            // En el encabezado general puedes mostrar también la etapa de tutorías si quieres
            'unidadHasta'         => "Tutorías etapa: {$tutoriaEtapa}/3",
        ]);
    }

    public function export(Request $request)
    {
        // Si tu Export ya arma todo, basta con instanciarlo así:
        return Excel::download(new CalificacionesExport, 'calificaciones.xlsx');
        
        // Si quieres pasarle datos, cambia tu Export para recibirlos y haz:
        // [$resumen, $unidadHasta] = $this->buildResumen(); // helper tuyo
        // return Excel::download(new CalificacionesExport($resumen, $unidadHasta), 'calificaciones.xlsx');
    }

    // ===================== Helpers =====================

    /**
     * Progreso del cuatrimestre 0..1 (por fechas reales ene-abr, may-ago, sep-dic).
     */
    private function progresoCuatri(): float
    {
        [$ini, $fin, , $hoy] = $this->rangoCuatriActual();

        $totalDias = $ini->diffInDays($fin) + 1;   // inclusivo
        $pasados   = $ini->diffInDays($hoy) + 1;   // inclusivo
        $pasados   = max(0, min($pasados, $totalDias));

        if ($totalDias <= 0) return 0.0;
        return max(0.0, min(1.0, $pasados / $totalDias));
    }

    /**
     * Dado un número de unidades (2,3,4,5,7, ...), regresa la unidad vigente (1..n)
     * mapeando con el progreso actual del cuatrimestre.
     */
    private function unidadIndexFor(int $n, ?float $progOverride = null): int
    {
        $n = max(1, $n);
        $prog = is_null($progOverride) ? $this->progresoCuatri() : max(0.0, min(1.0, $progOverride));
        $idx = (int)ceil($prog * $n);
        return max(1, min($idx, $n));
    }

    private function rangoCuatriActual(): array
    {
        $tz   = 'America/Mexico_City';
        $hoy  = Carbon::now($tz)->startOfDay();
        $anio = (int)$hoy->format('Y');
        $mes  = (int)$hoy->format('n');

        if ($mes >= 1 && $mes <= 4) {               // Ene–Abr
            $ini = Carbon::create($anio, 1, 1, 0, 0, 0, $tz)->startOfDay();
            $fin = Carbon::create($anio, 4, 30, 23, 59, 59, $tz)->endOfDay();
        } elseif ($mes >= 5 && $mes <= 8) {         // May–Ago
            $ini = Carbon::create($anio, 5, 1, 0, 0, 0, $tz)->startOfDay();
            $fin = Carbon::create($anio, 8, 31, 23, 59, 59, $tz)->endOfDay();
        } else {                                     // Sep–Dic
            $ini = Carbon::create($anio, 9, 1, 0, 0, 0, $tz)->startOfDay();
            $fin = Carbon::create($anio, 12, 31, 23, 59, 59, $tz)->endOfDay();
        }

        return [$ini, $fin, null, $hoy];
    }

    private function mapearTutorias($nombreSubmodulo): ?string
    {
        $nombre = $this->quitarAcentos(mb_strtolower($nombreSubmodulo ?? ''));

        if (strpos($nombre, 'presentacion') !== false) return 'Presentación del Tutor';
        if (strpos($nombre, 'tutoria') !== false && strpos($nombre, '1') !== false) return '1er Tutoría Grupal';
        if (strpos($nombre, 'tutoria') !== false && strpos($nombre, '2') !== false) return '2da Tutoría Grupal';
        if (strpos($nombre, 'tutoria') !== false && strpos($nombre, '3') !== false) return '3er Tutoría Grupal';

        if (strpos($nombre, 'proyecto') !== false) return 'Registro de Proyecto Institucional';
        if (strpos($nombre, 'parcial')  !== false) return 'Informe Parcial';
        if (strpos($nombre, 'global')   !== false) return 'Informe Global';

        return null;
    }

    private function quitarAcentos(string $s): string
    {
        $map = [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
            'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u',
            'ñ'=>'n','Ñ'=>'n'
        ];
        return strtr($s, $map);
    }
}
