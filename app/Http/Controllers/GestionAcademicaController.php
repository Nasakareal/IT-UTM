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
    private function qnorm(string $s): string {
        $s = mb_strtoupper($s, 'UTF-8');
        $s = strtr($s, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N']);
        return str_replace([' ', '-'], '', trim($s));
    }

    private function quarterNameFromDate(Carbon $d): string {
        $y = $d->year;
        $m = (int)$d->month;
        if ($m >= 1 && $m <= 4)   return "ENERO-ABRIL $y";
        if ($m >= 5 && $m <= 8)   return "MAYO-AGOSTO $y";
        return "SEPTIEMBRE-DICIEMBRE $y";
    }

    private function quarterRange(string $quarterName): array {
        // ENERO-ABRIL YYYY | MAYO-AGOSTO YYYY | SEPTIEMBRE-DICIEMBRE YYYY
        [$label, $year] = [trim(preg_replace('/\s+\d{4}$/','',$quarterName)), (int)substr($quarterName,-4)];
        switch ($label) {
            case 'ENERO-ABRIL':
                return [Carbon::create($year,1,1)->startOfDay(),  Carbon::create($year,4,30)->endOfDay()];
            case 'MAYO-AGOSTO':
                return [Carbon::create($year,5,1)->startOfDay(),  Carbon::create($year,8,31)->endOfDay()];
            default:
                return [Carbon::create($year,9,1)->startOfDay(),  Carbon::create($year,12,31)->endOfDay()];
        }
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->teacher_id) {
            return back()->with('error', 'Este usuario no tiene asignado un teacher_id.');
        }

        // === 1) Catálogo local de cuatrimestres (para alinear nombres y orden)
        $quartersLocal = DB::table('cuatrimestres')
            ->orderBy('fecha_inicio','desc')
            ->get(['id','nombre','fecha_inicio','fecha_fin']);

        // === 2) Cuatrimestres que realmente tiene el profe
        $ch = DB::connection('cargahoraria');

        // 2a) Por schedule_history (nombres normalizados)
        $normFromSH = $ch->table('schedule_history')
            ->where('teacher_id', $user->teacher_id)
            ->whereNotNull('quarter_name_en')
            ->groupBy('quarter_name_en')
            ->pluck('quarter_name_en')
            ->map(fn($n) => $this->qnorm((string)$n))
            ->unique();

        // 2b) Por teacher_subjects -> derivar nombre de cuatri desde la fecha
        $rowsTS = $ch->table('teacher_subjects')->where('teacher_id',$user->teacher_id)
            ->select('fyh_actualizacion','fyh_creacion')->get();

        $quartersFromTS = collect();
        foreach ($rowsTS as $r) {
            $dt = $r->fyh_actualizacion ?? $r->fyh_creacion;
            if (!$dt) continue;
            $qname = $this->quarterNameFromDate(Carbon::parse($dt));
            $quartersFromTS->push($this->qnorm($qname));
        }
        $quartersFromTS = $quartersFromTS->unique();

        // 2c) Local visibles: solo los que matchean por nombre normalizado con SH o TS
        $quartersVisible = $quartersLocal->filter(function($q) use ($normFromSH, $quartersFromTS) {
            $norm = $this->qnorm($q->nombre);
            return $normFromSH->contains($norm) || $quartersFromTS->contains($norm);
        })->values();

        // Fallback: si no hubo visibles, usar el cuatrimestre "actual" por fechas del catálogo local
        if ($quartersVisible->isEmpty()) {
            $hoy = Carbon::now();
            $actual = DB::table('cuatrimestres')
                ->whereDate('fecha_inicio','<=',$hoy)
                ->whereDate('fecha_fin','>=',$hoy)
                ->orderBy('fecha_inicio','desc')->first();
            if ($actual) $quartersVisible = collect([$actual]);
            else $quartersVisible = $quartersLocal->take(1);
        }

        // === 3) Resolver cuatrimestre seleccionado (solo válido si está en visibles)
        $quarterParam = trim((string)$request->get('quarter_name',''));
        $cuatri = null;
        if ($quarterParam !== '') {
            $cuatri = $quartersVisible->first(fn($q) => trim($q->nombre) === $quarterParam);
        }
        if (!$cuatri) $cuatri = $quartersVisible->first();

        $quarter_name = trim($cuatri->nombre);
        [$iniQ, $finQ] = $this->quarterRange($quarter_name);
        $totalDias = $iniQ->diffInDays($finQ) + 1;
        $diasTranscurridos = max(1, min($totalDias, $iniQ->diffInDays(Carbon::now()) + 1));

        // === 4) Snapshot local de ese cuatri
        $materias = DB::table('materias_docentes_snapshots')
            ->select('materia','unidades','programa','grupo')
            ->where('teacher_id', $user->teacher_id)
            ->where('cuatrimestre_id', $cuatri->id)
            ->orderBy('programa')->orderBy('materia')->orderBy('grupo')
            ->get();

        // === 5) Si no hay snapshot, intentamos SCHEDULE_HISTORY; si sigue vacío, fallback a TEACHER_SUBJECTS
        if ($materias->isEmpty()) {
            $qNorm = $this->qnorm($quarter_name);
            $iniBuf = $iniQ->copy()->subDays(15)->format('Y-m-d H:i:s');
            $finBuf = $finQ->copy()->addDays(15)->format('Y-m-d H:i:s');

            // 5a) schedule_history
            $remotas = $ch->table('schedule_history as sh')
                ->leftJoin('subjects as s','sh.subject_id','=','s.subject_id')
                ->leftJoin('programs as p','s.program_id','=','p.program_id')
                ->leftJoin('groups   as g','sh.group_id','=','g.group_id')
                ->where('sh.teacher_id',$user->teacher_id)
                ->where(function($w) use ($qNorm,$iniBuf,$finBuf){
                    $w->whereRaw("REPLACE(REPLACE(UPPER(COALESCE(sh.quarter_name_en,'')),' ',''),'-','') = ?", [$qNorm])
                      ->orWhereBetween('sh.fecha_registro', [$iniBuf,$finBuf]);
                })
                ->selectRaw("
                    COALESCE(s.subject_name, sh.subject_name) as materia,
                    COALESCE(s.unidades, 1)                   as unidades,
                    COALESCE(p.program_name, sh.program)      as programa,
                    COALESCE(g.group_name, sh.group_name)     as grupo,
                    sh.teacher_id                              as teacher_id,
                    sh.subject_id                              as subject_id,
                    sh.group_id                                as group_id,
                    COALESCE(p.program_id, s.program_id)       as program_id
                ")
                ->groupBy('materia','unidades','programa','grupo','teacher_id','subject_id','group_id','program_id')
                ->orderBy('programa')->orderBy('materia')->orderBy('grupo')
                ->get();

            // 5b) fallback a teacher_subjects si schedule_history no trae nada
            if ($remotas->isEmpty()) {
                $remotas = $ch->table('teacher_subjects as ts')
                    ->join('subjects as s','ts.subject_id','=','s.subject_id')
                    ->join('programs as p','s.program_id','=','p.program_id')
                    ->join('groups   as g','ts.group_id','=','g.group_id')
                    ->where('ts.teacher_id',$user->teacher_id)
                    ->where(function($w) use ($iniQ,$finQ){
                        // Mapear por fecha del TS o del group al rango del cuatrimestre seleccionado
                        $w->whereBetween(DB::raw('COALESCE(ts.fyh_actualizacion, ts.fyh_creacion)'), [$iniQ, $finQ])
                          ->orWhereBetween(DB::raw('COALESCE(g.fyh_actualizacion, g.fyh_creacion)'), [$iniQ, $finQ]);
                    })
                    ->selectRaw("
                        s.subject_name as materia,
                        COALESCE(s.unidades,1) as unidades,
                        p.program_name as programa,
                        g.group_name   as grupo,
                        ts.teacher_id  as teacher_id,
                        s.subject_id   as subject_id,
                        g.group_id     as group_id,
                        p.program_id   as program_id
                    ")
                    ->groupBy('materia','unidades','programa','grupo','teacher_id','subject_id','group_id','program_id')
                    ->orderBy('programa')->orderBy('materia')->orderBy('grupo')
                    ->get();
            }

            if ($remotas->isNotEmpty()) {
                foreach ($remotas as $r) {
                    DB::table('materias_docentes_snapshots')->updateOrInsert(
                        [
                            'teacher_id'      => $r->teacher_id,
                            'materia'         => $r->materia,
                            'grupo'           => $r->grupo,
                            'programa'        => $r->programa,
                            'cuatrimestre_id' => $cuatri->id,
                        ],
                        [
                            'unidades'     => (int)$r->unidades,
                            'subject_id'   => $r->subject_id,
                            'group_id'     => $r->group_id,
                            'program_id'   => $r->program_id,
                            'quarter_name' => $quarter_name,
                            'source'       => $remotas === 'schedule_history' ? 'schedule_history' : 'teacher_subjects',
                            'captured_at'  => now(),
                            'updated_at'   => now(),
                            'created_at'   => now(),
                        ]
                    );
                }
                $materias = $remotas->map(fn($r)=>(object)[
                    'materia'=>$r->materia,
                    'unidades'=>(int)$r->unidades,
                    'programa'=>$r->programa,
                    'grupo'=>$r->grupo,
                ]);
            } else {
                $materias = collect(); // sin tronar
            }
        }

        // === 6) Documentos (igual que tu lógica)
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
            'quarters'     => $quartersVisible,   // solo los que sí tiene el profe
            'quarter_name' => $quarter_name,
        ]);
    }
}
