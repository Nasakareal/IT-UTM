<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CalificacionDocumento;
use App\Models\DocumentoSubido;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CalificacionesExport;

class CalificacionDocumentoController extends Controller
{
    public function index(Request $request)
    {
        $progresoCuatri = $this->progresoCuatri();
        $tutoriaEtapa   = $this->unidadIndexFor(3);

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
        $unidadRequeridaPorTipo = [
            '1er Tutoría Grupal' => 1,
            '2da Tutoría Grupal' => 2,
            '3er Tutoría Grupal' => 3,
        ];

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

        $userToTeacher = $profesores->pluck('teacher_id','id');

        $materiasPorTeacher = DB::connection('cargahoraria')
            ->table('teacher_subjects')
            ->select('teacher_id', DB::raw('COUNT(DISTINCT CONCAT(subject_id,"-",group_id)) as total_materias'))
            ->whereIn('teacher_id', $teacherIdsBase)
            ->groupBy('teacher_id')
            ->pluck('total_materias','teacher_id');

        $allowedByUPerTeacher = DB::connection('cargahoraria')
            ->table('teacher_subjects as ts')
            ->join('subjects as s','s.subject_id','=','ts.subject_id')
            ->whereIn('ts.teacher_id', $teacherIdsBase)
            ->select('ts.teacher_id','s.unidades')
            ->get()
            ->groupBy('teacher_id')
            ->map(function($rows) {
                $cutoffs = [];
                foreach ($rows as $r) {
                    $uTotal = max(1, (int)$r->unidades);
                    $cutoffs[] = (int)ceil($uTotal / 2);
                }
                $allowed = [1=>0, 2=>0, 3=>0];
                foreach ([1,2,3] as $u) {
                    $allowed[$u] = count(array_filter($cutoffs, fn($c) => $c >= $u));
                }
                return $allowed;
            });

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
                sort($actuales);
                $cnt = count($actuales);
                if ($cnt === 0) return 1;
                $mid = intdiv($cnt, 2);
                if ($cnt % 2 === 0) return (int) round(($actuales[$mid-1] + $actuales[$mid]) / 2);
                return (int) $actuales[$mid];
            });

        $fechasTutor = DB::table('submodulos as sm')
            ->join('subsections as ss','ss.id','=','sm.subsection_id')
            ->where('ss.modulo_id',5)
            ->select('sm.titulo','sm.fecha_apertura','sm.fecha_cierre','sm.fecha_limite')
            ->get()
            ->reduce(function($acc,$r){
                $acc[$r->titulo] = [
                    'apertura' => $r->fecha_apertura,
                    'cierre'   => $r->fecha_cierre,
                    'limite'   => $r->fecha_limite,
                ];
                return $acc;
            }, []);

        $avgPorDocumento = DB::table('calificacion_documentos')
            ->select('documento_id', DB::raw('AVG(calificacion) as prom_item'))
            ->groupBy('documento_id');

        $ultimosIds = DB::table('documentos_subidos')
            ->whereIn('user_id', $userIdsBase)
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('user_id','tipo_documento','unidad');

        $rowsDocs = DB::table('documentos_subidos as ds')
            ->joinSub($ultimosIds, 'u', 'u.id', '=', 'ds.id')
            ->leftJoinSub($avgPorDocumento,'pdoc','pdoc.documento_id','=','ds.id')
            ->whereIn('ds.user_id',$userIdsBase)
            ->where(function ($q) use ($especiales) {
                $q->whereNotIn('ds.tipo_documento', $especiales)
                  ->orWhere(function ($q2) use ($especiales) {
                      $q2->whereIn('ds.tipo_documento', $especiales)
                         ->where(function ($q3) {
                             $q3->whereNull('ds.unidad')
                                ->orWhere('ds.unidad', 1);
                         });
                  });
            })
            ->select('ds.id','ds.user_id','ds.unidad','ds.tipo_documento','pdoc.prom_item')
            ->get();

        $promPorSA = DB::table('calificacion_submodulo_archivos')
            ->select('submodulo_archivo_id', DB::raw('AVG(calificacion) as prom_por_sa'))
            ->groupBy('submodulo_archivo_id');

        $baseSub = DB::table('submodulo_archivos as sa')
            ->join('submodulos as sm','sm.id','=','sa.submodulo_id')
            ->join('subsections as ss','ss.id','=','sm.subsection_id')
            ->leftJoinSub($promPorSA, 'pcsa', 'pcsa.submodulo_archivo_id', '=', 'sa.id')
            ->leftJoin('calificacion_submodulo_archivos as csa','csa.submodulo_archivo_id','=','sa.id')
            ->where('ss.modulo_id', 5)
            ->whereIn('sa.user_id', $userIdsBase)
            ->selectRaw('
                COALESCE(csa.profesor_id, sa.user_id) as profesor_id,
                sm.titulo as submodulo_titulo,
                sa.created_at as sa_created_at,
                sm.fecha_apertura,
                sm.fecha_cierre,
                pcsa.prom_por_sa
            ');

        $rowsSubs = DB::query()
            ->fromSub($baseSub, 'x')
            ->selectRaw('
                x.profesor_id,
                x.submodulo_titulo,
                SUM(CASE WHEN x.sa_created_at BETWEEN x.fecha_apertura AND x.fecha_cierre THEN 1 ELSE 0 END) as entregados_validos,
                AVG(x.prom_por_sa) as prom_item_total
            ')
            ->groupBy('x.profesor_id','x.submodulo_titulo')
            ->get();

        $entPorTipo   = [];
        $promPorTipo  = [];
        $buckets      = [];

        foreach ($rowsDocs as $row) {
            $uid  = (int)$row->user_id;
            $tipo = trim($row->tipo_documento ?? '');
            if ($tipo === '') continue;

            $u = (int)($row->unidad ?? 1);
            if ($u < 1) $u = 1;

            $buckets[$uid][$tipo][$u] = $buckets[$uid][$tipo][$u] ?? [];
            $buckets[$uid][$tipo][$u][] = $row->prom_item;
        }

        foreach ($buckets as $uid => $tipos) {
            $tid = (int)($userToTeacher[$uid] ?? 0);
            $allowed = $allowedByUPerTeacher[$tid] ?? [1=>0,2=>0,3=>0];

            foreach ($tipos as $tipo => $byU) {
                $ent = 0;
                $sum = 0.0;
                $n   = 0;

                foreach ($byU as $u => $vals) {
                    $cap = 0;
                    if ($u <= 3) {
                        $cap = (int)($allowed[$u] ?? 0);
                    } else {
                        $cap = 0;
                    }

                    if ($cap <= 0) continue;

                    $cant = min(count($vals), $cap);
                    $ent += $cant;

                    $tomados = 0;
                    foreach ($vals as $v) {
                        if ($tomados >= $cant) break;
                        if (!is_null($v)) {
                            $sum += (float)$v;
                            $n   += 1;
                        }
                        $tomados++;
                    }
                }

                if ($ent > 0) {
                    $entPorTipo[$uid][$tipo] = ($entPorTipo[$uid][$tipo] ?? 0) + $ent;
                }

                if ($n > 0) {
                    if (!isset($promPorTipo[$uid][$tipo])) $promPorTipo[$uid][$tipo] = ['suma'=>0.0,'n'=>0];
                    $promPorTipo[$uid][$tipo]['suma'] += $sum;
                    $promPorTipo[$uid][$tipo]['n']    += $n;
                }
            }
        }

        foreach ($rowsSubs as $row) {
            $uid  = (int)$row->profesor_id;
            $tipo = $this->mapearTutorias($row->submodulo_titulo);
            if (!$tipo) continue;

            $permitidoEtapa = true;
            if (isset($unidadRequeridaPorTipo[$tipo])) {
                $permitidoEtapa = ($tutoriaEtapa >= $unidadRequeridaPorTipo[$tipo]);
            }
            if (!$permitidoEtapa) continue;

            $entPorTipo[$uid][$tipo] = ($entPorTipo[$uid][$tipo] ?? 0) + (int)$row->entregados_validos;

            if (!is_null($row->prom_item_total)) {
                if (!isset($promPorTipo[$uid][$tipo])) {
                    $promPorTipo[$uid][$tipo] = ['suma'=>0.0,'n'=>0];
                }
                $promPorTipo[$uid][$tipo]['suma'] += (float)$row->prom_item_total;
                $promPorTipo[$uid][$tipo]['n']    += 1;
            }
        }

        $hoy = Carbon::now('America/Mexico_City');
        $resumenPorDocumento = [];

        foreach ($profesores as $p) {
            $uid = (int)$p->id;
            $tid = (int)$p->teacher_id;

            $totalMaterias = (int)($materiasPorTeacher[$tid] ?? 0);
            $unidadVigenteProfe = (int)($unidadesPorTeacher[$tid] ?? 1);

            $detallesTipos = [];

            foreach (array_merge($especiales, $tiposEstandar) as $tipo) {
                $esperados  = $totalMaterias;
                $entregados = (int)($entPorTipo[$uid][$tipo] ?? 0);
                $entregados = $esperados > 0 ? min($entregados, $esperados) : 0;

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

            foreach ($generalesTutor as $tipo) {
                $req           = $unidadRequeridaPorTipo[$tipo] ?? null;
                $habilitaEtapa = is_null($req) || ($tutoriaEtapa >= $req);

                $f = $fechasTutor[$tipo] ?? null;
                $abrio = $f && $f['apertura'] ? $hoy->greaterThanOrEqualTo(Carbon::parse($f['apertura'])) : false;

                $esperados  = ($habilitaEtapa && $abrio) ? 1 : 0;

                $entregados = (int)($entPorTipo[$uid][$tipo] ?? 0);
                $entregados = $esperados > 0 ? min($entregados, $esperados) : 0;

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
                'unidad_hasta' => $unidadVigenteProfe,
                'docs'         => $detallesTipos,
            ];
        }

        usort($resumenPorDocumento, fn($a,$b) => strcmp($a['nombre'], $b['nombre']));

        return view('settings.calificaciones.index', [
            'resumenPorDocumento' => $resumenPorDocumento,
            'unidadHasta'         => "Tutorías etapa: {$tutoriaEtapa}/3",
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'documento_id' => 'required|exists:documentos_subidos,id',
            'calificacion' => 'required|integer|between:0,10',
        ]);

        $documentoId = $request->input('documento_id');
        $evaluadorId = Auth::id();

        CalificacionDocumento::updateOrCreate(
            ['documento_id' => $documentoId, 'evaluador_id' => $evaluadorId],
            ['calificacion' => (int)$request->input('calificacion')]
        );

        return back()->with('success', 'Calificación guardada correctamente.');
    }

    public function export(Request $request)
    {
        return Excel::download(new CalificacionesExport, 'calificaciones.xlsx');
    }

    private function progresoCuatri(): float
    {
        [$ini, $fin, , $hoy] = $this->rangoCuatriActual();

        $totalDias = $ini->diffInDays($fin) + 1;
        $pasados   = $ini->diffInDays($hoy) + 1;
        $pasados   = max(0, min($pasados, $totalDias));

        if ($totalDias <= 0) return 0.0;
        return max(0.0, min(1.0, $pasados / $totalDias));
    }

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

        if ($mes >= 1 && $mes <= 4) {
            $ini = Carbon::create($anio, 1, 1, 0, 0, 0, $tz)->startOfDay();
            $fin = Carbon::create($anio, 4, 30, 23, 59, 59, $tz)->endOfDay();
        } elseif ($mes >= 5 && $mes <= 8) {
            $ini = Carbon::create($anio, 5, 1, 0, 0, 0, $tz)->startOfDay();
            $fin = Carbon::create($anio, 8, 31, 23, 59, 59, $tz)->endOfDay();
        } else {
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
