<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\DocumentoSubido;
use App\Models\FirmaLote;

class GestionAcademicaController extends Controller
{
    private function quarterRange(string $quarterName): array {
        [$label, $year] = [trim(preg_replace('/\s+\d{4}$/','',$quarterName)), (int)substr($quarterName,-4)];
        switch ($label) {
            case 'ENERO-ABRIL':   return [Carbon::create($year,1,1)->startOfDay(),  Carbon::create($year,4,30)->endOfDay()];
            case 'MAYO-AGOSTO':   return [Carbon::create($year,5,1)->startOfDay(),  Carbon::create($year,8,31)->endOfDay()];
            default:              return [Carbon::create($year,9,1)->startOfDay(),  Carbon::create($year,12,31)->endOfDay()];
        }
    }

    /** Sincroniza snapshots EXACTO contra teacher_subjects del esquema remoto (solo vigente) */
    private function syncSnapshotsExacto(int $teacherId, int $cuatrimestreId, string $quarterName): void
    {
        DB::statement("SET collation_connection = 'utf8mb4_spanish_ci'");
        $remoteDb = config('database.connections.cargahoraria.database');
        if (!$remoteDb) return;

        // 1) DELETE lo que ya no exista en TS
        $sqlDel = "
            DELETE s
              FROM materias_docentes_snapshots s
              LEFT JOIN `{$remoteDb}`.teacher_subjects ts
                     ON ts.teacher_id = s.teacher_id
                    AND ts.subject_id = s.subject_id
                    AND ts.group_id   = s.group_id
             WHERE s.teacher_id = ?
               AND s.cuatrimestre_id = ?
               AND ts.teacher_subject_id IS NULL
        ";
        DB::delete($sqlDel, [$teacherId, $cuatrimestreId]);

        // 2) INSERT lo que falte desde TS
        $sqlIns = "
            INSERT INTO materias_docentes_snapshots
                (teacher_id, materia, grupo, programa, unidades,
                 subject_id, group_id, program_id,
                 cuatrimestre_id, quarter_name, captured_at, source, created_at, updated_at)
            SELECT
                ts.teacher_id,
                s.subject_name,
                g.group_name,
                p.program_name,
                COALESCE(s.unidades, 1),
                ts.subject_id,
                ts.group_id,
                p.program_id,
                ?, ?, NOW(), 'teacher_subjects', NOW(), NOW()
            FROM `{$remoteDb}`.teacher_subjects ts
            JOIN `{$remoteDb}`.subjects  s ON s.subject_id = ts.subject_id
            JOIN `{$remoteDb}`.groups    g ON g.group_id   = ts.group_id
            JOIN `{$remoteDb}`.programs  p ON p.program_id = s.program_id
            LEFT JOIN materias_docentes_snapshots snap
                   ON snap.teacher_id      = ts.teacher_id
                  AND snap.subject_id      = ts.subject_id
                  AND snap.group_id        = ts.group_id
                  AND snap.cuatrimestre_id = ?
            WHERE ts.teacher_id = ?
              AND snap.id IS NULL
        ";
        DB::insert($sqlIns, [$cuatrimestreId, $quarterName, $cuatrimestreId, $teacherId]);
    }

    /** ¿Existen TS del profe en el rango del cuatri vigente? */
    private function existeTSenVigente(int $teacherId, object $vigente): bool
    {
        $exists = DB::connection('cargahoraria')->table('teacher_subjects as ts')
            ->leftJoin('groups as g', 'g.group_id', '=', 'ts.group_id')
            ->where('ts.teacher_id', $teacherId)
            ->where(function($w) use ($vigente) {
                $w->whereBetween(DB::raw('COALESCE(ts.fyh_actualizacion, ts.fyh_creacion)'), [$vigente->fecha_inicio, $vigente->fecha_fin])
                  ->orWhereBetween(DB::raw('COALESCE(g.fyh_actualizacion, g.fyh_creacion)'),   [$vigente->fecha_inicio, $vigente->fecha_fin]);
            })
            ->limit(1)
            ->exists();
        return (bool)$exists;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->teacher_id) {
            return back()->with('error', 'Este usuario no tiene asignado un teacher_id.');
        }

        // Vigente por catálogo (solo metadata)
        $hoy = Carbon::now();
        $vigente = DB::table('cuatrimestres')
            ->whereDate('fecha_inicio','<=',$hoy)
            ->whereDate('fecha_fin','>=',$hoy)
            ->orderBy('fecha_inicio','desc')
            ->first();

        // Cuatrimestres del PROFESOR ya en SNAPSHOT (histórico real)
        $quartersDeProfe = DB::table('materias_docentes_snapshots as m')
            ->join('cuatrimestres as c', 'c.id', '=', 'm.cuatrimestre_id')
            ->where('m.teacher_id', $user->teacher_id)
            ->select('c.id','c.nombre','c.fecha_inicio','c.fecha_fin')
            ->distinct()
            ->orderBy('c.fecha_inicio','desc')
            ->get();

        // Lista visible: solo snapshots del profe
        $quartersVisible = $quartersDeProfe->values();

        // Si el vigente tiene TS reales y aún no existe snapshot, opcionalmente lo agregamos para que se pueda sincronizar
        if ($vigente && !$quartersVisible->first(fn($q) => $q->id === $vigente->id)) {
            if ($this->existeTSenVigente((int)$user->teacher_id, $vigente)) {
                $quartersVisible->prepend($vigente);
            }
        }

        if ($quartersVisible->isEmpty()) {
            return view('modulos.gestion_academica', [
                'documentos'   => [],
                'quarters'     => [],
                'quarter_name' => null,
            ]);
        }

        // *** CLAVE: seleccionar por ID, no por nombre ***
        $quarterId = (int) $request->get('quarter_id', 0);
        $cuatri = null;

        if ($quarterId > 0) {
            $cuatri = $quartersVisible->first(fn($q) => (int)$q->id === $quarterId);
            if (!$cuatri) {
                return back()->with('error','El cuatrimestre seleccionado no está disponible para este profesor.');
            }
        } else {
            // Por defecto: el más reciente visible (sin inventar por fechas)
            $cuatri = $quartersVisible->first();
        }

        $quarter_name = trim($cuatri->nombre);
        [$iniQ, $finQ] = $this->quarterRange($quarter_name);

        // Solo sincronizamos si ES el vigente (por ID) y hay TS reales en ese rango
        $esVigente = $vigente && ((int)$cuatri->id === (int)$vigente->id);
        if ($esVigente && $this->existeTSenVigente((int)$user->teacher_id, $vigente)) {
            $this->syncSnapshotsExacto((int)$user->teacher_id, (int)$cuatri->id, $quarter_name);
        }

        // *** Leer SOLO por cuatrimestre_id (sin OR por nombre, sin fallbacks) ***
        $materias = DB::table('materias_docentes_snapshots')
            ->select('materia','unidades','programa','grupo')
            ->where('teacher_id', $user->teacher_id)
            ->where('cuatrimestre_id', $cuatri->id)
            ->orderBy('programa')->orderBy('materia')->orderBy('grupo')
            ->get();

        // Cálculo de unidad actual (solo para UI)
        $totalDias = Carbon::parse($cuatri->fecha_inicio)->diffInDays(Carbon::parse($cuatri->fecha_fin)) + 1;
        $diasTranscurridos = max(1, min($totalDias, Carbon::parse($cuatri->fecha_inicio)->diffInDays(Carbon::now()) + 1));

        $documentos = [];
        $VENTANA_EDIT_MIN = config('academico.minutos_edicion', 120);
        $lotesCache = [];

        foreach ($materias as $m) {
            $totalUnidades = max(1,(int)$m->unidades);
            $diasPorUnidad = (int)ceil($totalDias / max(1,$totalUnidades));
            $unidadActual  = min($totalUnidades,(int)ceil($diasTranscurridos / max(1,$diasPorUnidad)));

            for ($u=1; $u<=$totalUnidades; $u++) {
                $keyLote = $m->materia.'|'.$m->grupo.'|'.$u.'|'.$quarter_name;
                if (!isset($lotesCache[$keyLote])) {
                    $lotesCache[$keyLote] = FirmaLote::where('user_id',$user->id)
                        ->where('materia',$m->materia)->where('grupo',$m->grupo)->where('unidad',$u)
                        ->orderByDesc('firmado_at')->orderByDesc('id')->first();
                }
                $lote   = $lotesCache[$keyLote];
                $loteId = $lote->id ?? null;
                $acuseU = $lote->acuse_lote ?? null;

                if ($u===1) {
                    foreach ([
                        'Presentación de la Asignatura'=>'F-DA-GA-01 Presentación de la asignatura.xlsx',
                        'Planeación didáctica'=>'F-DA-GA-02 Planeación didáctica del programa de asignatura.docx',
                    ] as $tipo=>$plantilla) {
                        $registro = DocumentoSubido::where('user_id',$user->id)
                            ->where('materia',$m->materia)->where('grupo',$m->grupo)
                            ->where('unidad',1)->where('tipo_documento',$tipo)
                            ->where('quarter_name',$quarter_name)->first();

                        $createdAtIso = $registro?->created_at?->toIso8601String();
                        $deadlineIso=null; $editable=false;
                        $firmado = ($registro && ($registro->fecha_firma || $registro->firma_sig));
                        if ($registro && $registro->created_at) {
                            $deadline=$registro->created_at->copy()->addMinutes($VENTANA_EDIT_MIN);
                            $deadlineIso=$deadline->toIso8601String();
                            $editable=now()->lt($deadline);
                        }

                        $documentos[] = [
                            'quarter_name'=>$quarter_name,'materia'=>$m->materia,'programa'=>$m->programa,'grupo'=>$m->grupo,
                            'unidad'=>1,'documento'=>$tipo,'archivo'=>$plantilla,
                            'entregado'=>(bool)$registro,'archivo_subido'=>$registro->archivo??null,
                            'acuse'=>$registro->acuse_pdf??null,'acuse_lote'=>$acuseU,'lote_id'=>$loteId,
                            'es_actual'=>($unidadActual===1),'editable'=>$editable,
                            'created_at'=>$createdAtIso,'cierre_edicion_iso'=>$editable?$deadlineIso:null,'firmado'=>$firmado,
                        ];
                    }
                }

                foreach ([
                    'Seguimiento de la Planeación'=>'F-DA-GA-03 Seguimiento de la Planeación Didáctica.xlsx',
                    'Control de Asesorías'=>'F-DA-GA-06 Control de Asesorías.xlsx',
                    'Informe de Estudiantes No Acreditados'=>'F-DA-GA-05 Informe de Estudiantes No Acreditados.xlsx',
                    'Reporte de Evaluación Continua por Unidad de Aprendizaje (SIGO)'=>null,
                ] as $tipo=>$plantilla) {
                    $registro = DocumentoSubido::where('user_id',$user->id)
                        ->where('materia',$m->materia)->where('grupo',$m->grupo)
                        ->where('unidad',$u)->where('tipo_documento',$tipo)
                        ->where('quarter_name',$quarter_name)->first();

                    $createdAtIso = $registro?->created_at?->toIso8601String();
                    $deadlineIso=null; $editable=false;
                    $firmado = ($registro && ($registro->fecha_firma || $registro->firma_sig));
                    if ($registro && $registro->created_at) {
                        $deadline=$registro->created_at->copy()->addMinutes($VENTANA_EDIT_MIN);
                        $deadlineIso=$deadline->toIso8601String();
                        $editable=now()->lt($deadline);
                    }

                    $documentos[] = [
                        'quarter_name'=>$quarter_name,'materia'=>$m->materia,'programa'=>$m->programa,'grupo'=>$m->grupo,
                        'unidad'=>$u,'documento'=>$tipo,'archivo'=>$plantilla,
                        'entregado'=>(bool)$registro,'archivo_subido'=>$registro->archivo??null,
                        'acuse'=>$registro->acuse_pdf??null,'acuse_lote'=>$acuseU,'lote_id'=>$loteId,
                        'es_actual'=>($u===$unidadActual),'editable'=>$editable,
                        'created_at'=>$createdAtIso,'cierre_edicion_iso'=>$editable?$deadlineIso:null,'firmado'=>$firmado,
                    ];
                }

                if ($u===$totalUnidades) {
                    $tipoFinal='Reporte Cuatrimestral de la Evaluación Continua (SIGO)';
                    $registroFinal = DocumentoSubido::where('user_id',$user->id)
                        ->where('materia',$m->materia)->where('grupo',$m->grupo)
                        ->where('unidad',$u)->where('tipo_documento',$tipoFinal)
                        ->where('quarter_name',$quarter_name)->first();

                    $createdAtIso = $registroFinal?->created_at?->toIso8601String();
                    $deadlineIso=null; $editable=false;
                    $firmado = ($registroFinal && ($registroFinal->fecha_firma || $registroFinal->firma_sig));
                    if ($registroFinal && $registroFinal->created_at) {
                        $deadline=$registroFinal->created_at->copy()->addMinutes($VENTANA_EDIT_MIN);
                        $deadlineIso=$deadline->toIso8601String();
                        $editable=now()->lt($deadline);
                    }

                    $documentos[] = [
                        'quarter_name'=>$quarter_name,'materia'=>$m->materia,'programa'=>$m->programa,'grupo'=>$m->grupo,
                        'unidad'=>$u,'documento'=>$tipoFinal,'archivo'=>null,
                        'entregado'=>(bool)$registroFinal,'archivo_subido'=>$registroFinal->archivo??null,
                        'acuse'=>$registroFinal->acuse_pdf??null,'acuse_lote'=>$acuseU,'lote_id'=>$loteId,
                        'es_actual'=>($u===$unidadActual),'editable'=>$editable,
                        'created_at'=>$createdAtIso,'cierre_edicion_iso'=>$editable?$deadlineIso:null,'firmado'=>$firmado,
                    ];
                }
            }
        }

        return view('modulos.gestion_academica', [
            'documentos'   => $documentos,
            'quarters'     => $quartersVisible,
            'quarter_name' => $quarter_name,
        ]);
    }
}
