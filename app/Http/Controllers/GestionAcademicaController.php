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
        $s = strtr($s, [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
            'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U','ü'=>'U','ñ'=>'N'
        ]);
        return str_replace([' ', '-'], '', trim($s));
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->teacher_id) {
            return back()->with('error', 'Este usuario no tiene asignado un teacher_id.');
        }

        // Catálogo local ordenado
        $quartersAll = DB::table('cuatrimestres')
            ->orderBy('fecha_inicio', 'desc')
            ->get(['id','nombre','fecha_inicio','fecha_fin']);

        if ($quartersAll->isEmpty()) {
            // nunca mostramos avisos en la vista: mandamos uno válido
            return view('modulos.gestion_academica', [
                'documentos'   => [],
                'quarters'     => collect(),
                'quarter_name' => null,
            ]);
        }

        // Cuatrimestre "actual" por fechas (para forzar fallback)
        $hoy = Carbon::now();
        $cuatriActual = DB::table('cuatrimestres')
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin',   '>=', $hoy)
            ->orderBy('fecha_inicio','desc')
            ->first() ?? $quartersAll->first();

        // Conexión remota
        $ch = DB::connection('cargahoraria');

        // Nombres normalizados presentes en schedule_history para el profe
        $normFromSH = $ch->table('schedule_history')
            ->where('teacher_id', $user->teacher_id)
            ->whereNotNull('quarter_name_en')
            ->groupBy('quarter_name_en')
            ->pluck('quarter_name_en')
            ->map(fn($n) => $this->qnorm((string)$n))
            ->unique()
            ->values();

        // Helper por rango fecha_registro (buffer ±15d)
        $hasRowsForRange = function($ini, $fin) use ($ch, $user) {
            $iniBuf = Carbon::parse($ini)->copy()->subDays(15)->format('Y-m-d H:i:s');
            $finBuf = Carbon::parse($fin)->copy()->addDays(15)->format('Y-m-d H:i:s');
            return $ch->table('schedule_history')
                ->where('teacher_id', $user->teacher_id)
                ->whereBetween('fecha_registro', [$iniBuf, $finBuf])
                ->limit(1)->exists();
        };

        // *** SOLO mostramos cuatrimestres que el profe realmente tenga ***
        $quartersVisible = $quartersAll->filter(function($q) use ($normFromSH, $hasRowsForRange) {
            $matchName = $normFromSH->contains($this->qnorm($q->nombre));
            $matchDate = $hasRowsForRange($q->fecha_inicio, $q->fecha_fin);
            return $matchName || $matchDate;
        })->values();

        // Si quedó vacío por cualquier desajuste, forzamos SOLO el actual (no más fantasmas)
        if ($quartersVisible->isEmpty() && $cuatriActual) {
            $quartersVisible = collect([$cuatriActual]);
        }

        // Resolver cuatri elegido: solo aceptamos los visibles
        $quarterParam = trim((string)$request->get('quarter_name',''));
        $cuatri = null;
        if ($quarterParam !== '') {
            $cuatri = $quartersVisible->first(fn($q) => trim($q->nombre) === $quarterParam);
        }
        if (!$cuatri) {
            $cuatri = $quartersVisible->first(); // el más reciente visible
        }

        $quarter_name = trim($cuatri->nombre);
        $inicio = Carbon::parse($cuatri->fecha_inicio);
        $fin    = Carbon::parse($cuatri->fecha_fin);
        $totalDias = $inicio->diffInDays($fin) + 1;
        $diasTranscurridos = max(1, min($totalDias, $inicio->diffInDays(Carbon::now()) + 1));

        // Snapshot local solo de este cuatri
        $materias = DB::table('materias_docentes_snapshots')
            ->select('materia','unidades','programa','grupo')
            ->where('teacher_id', $user->teacher_id)
            ->where('cuatrimestre_id', $cuatri->id)
            ->orderBy('programa')->orderBy('materia')->orderBy('grupo')
            ->get();

        // Si no hay snapshot, jalamos schedule_history del cuatri seleccionado (por nombre normalizado O fecha)
        if ($materias->isEmpty()) {
            $qNorm = $this->qnorm($quarter_name);
            $iniBuf = $inicio->copy()->subDays(15)->format('Y-m-d H:i:s');
            $finBuf = $fin->copy()->addDays(15)->format('Y-m-d H:i:s');

            $remotas = $ch->table('schedule_history as sh')
                ->leftJoin('subjects as s', 'sh.subject_id', '=', 's.subject_id')
                ->leftJoin('programs as p', 's.program_id', '=', 'p.program_id')
                ->leftJoin('groups   as g', 'sh.group_id',  '=', 'g.group_id')
                ->where('sh.teacher_id', $user->teacher_id)
                ->where(function($w) use ($qNorm, $iniBuf, $finBuf) {
                    $w->whereRaw("REPLACE(REPLACE(UPPER(COALESCE(sh.quarter_name_en,'')),' ',''),'-','') = ?", [$qNorm])
                      ->orWhereBetween('sh.fecha_registro', [$iniBuf, $finBuf]);
                })
                ->selectRaw('
                    COALESCE(s.subject_name, sh.subject_name) as materia,
                    COALESCE(s.unidades, 1)                   as unidades,
                    COALESCE(p.program_name, sh.program)      as programa,
                    COALESCE(g.group_name, sh.group_name)     as grupo,
                    sh.teacher_id                              as teacher_id,
                    sh.subject_id                              as subject_id,
                    sh.group_id                                as group_id,
                    COALESCE(p.program_id, s.program_id)       as program_id
                ')
                ->groupBy('materia','unidades','programa','grupo','teacher_id','subject_id','group_id','program_id')
                ->orderBy('programa')->orderBy('materia')->orderBy('grupo')
                ->get();

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
                            'source'       => 'schedule_history',
                            'captured_at'  => now(),
                            'updated_at'   => now(),
                            'created_at'   => now(),
                        ]
                    );
                }
                $materias = $remotas->map(fn($r)=>(object)[
                    'materia'=>$r->materia,'unidades'=>(int)$r->unidades,'programa'=>$r->programa,'grupo'=>$r->grupo
                ]);
            } else {
                $materias = collect(); // sin tronar ni 500
            }
        }

        // Construcción de documentos (igual a tu lógica actual)
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
