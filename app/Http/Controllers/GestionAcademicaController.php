<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\DocumentoSubido;
use App\Models\FirmaLote; // <-- para traer acuse general por unidad

class GestionAcademicaController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->teacher_id) {
            return back()->with('error', 'Este usuario no tiene asignado un teacher_id.');
        }

        // 1) Cuatrimestre activo
        $hoy    = Carbon::now();
        $cuatri = DB::table('cuatrimestres')
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin',   '>=', $hoy)
            ->first();

        if (!$cuatri) {
            return back()->with('error', 'No hay cuatrimestre activo configurado.');
        }

        $inicio            = Carbon::parse($cuatri->fecha_inicio);
        $fin               = Carbon::parse($cuatri->fecha_fin);
        $totalDias         = $inicio->diffInDays($fin) + 1;
        $diasTranscurridos = $inicio->diffInDays($hoy) + 1;

        // 2) Materias del docente — primero snapshot local
        $materias = DB::table('materias_docentes_snapshots')
            ->select('materia','unidades','programa','grupo')
            ->where('teacher_id', $user->teacher_id)
            ->when(isset($cuatri->id), fn($q) => $q->where('cuatrimestre_id', $cuatri->id))
            ->orderBy('programa')->orderBy('materia')->orderBy('grupo')
            ->get();

        // Si snapshot vacío, intentar en cargahoraria y persistir snapshot
        if ($materias->isEmpty()) {
            $remotas = DB::connection('cargahoraria')
                ->table('teacher_subjects as ts')
                ->join('subjects as s', 'ts.subject_id', '=', 's.subject_id')
                ->join('programs as p', 's.program_id', '=', 'p.program_id')
                ->join('groups as g', 'ts.group_id', '=', 'g.group_id')
                ->select(
                    's.subject_name as materia',
                    's.unidades',
                    'p.program_name as programa',
                    'g.group_name as grupo',
                    'ts.teacher_id',
                    's.subject_id',
                    'g.group_id',
                    'p.program_id'
                )
                ->where('ts.teacher_id', $user->teacher_id)
                ->groupBy(
                    's.subject_name','s.unidades','p.program_name','g.group_name',
                    'ts.teacher_id','s.subject_id','g.group_id','p.program_id'
                )
                ->orderBy('p.program_name')->orderBy('s.subject_name')->orderBy('g.group_name')
                ->get();

            if ($remotas->isEmpty()) {
                return back()->with('error', 'No se encontraron materias asignadas (ni en snapshot ni en cargahoraria).');
            }

            foreach ($remotas as $r) {
                DB::table('materias_docentes_snapshots')->updateOrInsert(
                    [
                        'teacher_id'      => $r->teacher_id,
                        'materia'         => $r->materia,
                        'grupo'           => $r->grupo,
                        'programa'        => $r->programa,
                        'cuatrimestre_id' => $cuatri->id ?? null,
                    ],
                    [
                        'unidades'     => (int) $r->unidades,
                        'subject_id'   => $r->subject_id,
                        'group_id'     => $r->group_id,
                        'program_id'   => $r->program_id,
                        'quarter_name' => $cuatri->nombre ?? null,
                        'source'       => 'cargahoraria',
                        'captured_at'  => now(),
                        'updated_at'   => now(),
                        'created_at'   => now(),
                    ]
                );
            }

            $materias = $remotas->map(fn($r) => (object)[
                'materia'  => $r->materia,
                'unidades' => (int)$r->unidades,
                'programa' => $r->programa,
                'grupo'    => $r->grupo,
            ]);
        }

        // 3) Tipos de documento
        $tipos = [
            'Reporte de Evaluación Continua por Unidad de Aprendizaje (SIGO)' => null,
            'Informe de Estudiantes No Acreditados'                           => 'F-DA-GA-05 Informe de Estudiantes No Acreditados.xlsx',
            'Control de Asesorías'                                            => 'F-DA-GA-06 Control de Asesorías.xlsx',
            'Seguimiento de la Planeación'                                    => 'F-DA-GA-03 Seguimiento de la Planeación Didáctica.xlsx',
        ];

        $documentos         = [];
        $VENTANA_EDIT_MIN   = config('academico.minutos_edicion', 120);

        // Cache local de lotes por unidad para evitar consultas repetidas
        $lotesCache = []; // clave: "materia|grupo|unidad" => FirmaLote|null

        // 4) Entradas por materia y unidad
        foreach ($materias as $m) {
            $totalUnidades = (int)$m->unidades;
            $diasPorUnidad = (int) ceil($totalDias / max(1, $totalUnidades));
            $unidadActual  = min($totalUnidades, (int) ceil($diasTranscurridos / max(1, $diasPorUnidad)));

            for ($u = 1; $u <= $totalUnidades; $u++) {

                // Lote (acuse general) de esta unidad (si existe)
                $keyLote = $m->materia.'|'.$m->grupo.'|'.$u;
                if (!array_key_exists($keyLote, $lotesCache)) {
                    $lotesCache[$keyLote] = FirmaLote::where('user_id', $user->id)
                        ->where('materia', $m->materia)
                        ->where('grupo',   $m->grupo)
                        ->where('unidad',  $u)
                        ->orderByDesc('firmado_at')
                        ->orderByDesc('id')
                        ->first();
                }
                $lote     = $lotesCache[$keyLote];
                $loteId   = $lote->id         ?? null;
                $acuseU   = $lote->acuse_lote ?? null;

                // Especiales (unidad 1)
                if ($u === 1) {
                    $documentosEspeciales = [
                        'Presentación de la Asignatura' => 'F-DA-GA-01 Presentación de la asignatura.xlsx',
                        'Planeación didáctica'          => 'F-DA-GA-02 Planeación didáctica del programa de asignatura.docx',
                    ];

                    foreach ($documentosEspeciales as $tipo => $plantilla) {
                        $registro = DocumentoSubido::where([
                            ['user_id',        $user->id],
                            ['materia',        $m->materia],
                            ['grupo',          $m->grupo],
                            ['unidad',         1],
                            ['tipo_documento', $tipo],
                        ])->first();

                        // --- cálculo de edición / deadline
                        $createdAtIso     = $registro?->created_at?->toIso8601String();
                        $deadlineIso      = null;
                        $editable         = false;
                        $firmado          = false;

                        if ($registro && $registro->created_at) {
                            $deadline  = $registro->created_at->copy()->addMinutes($VENTANA_EDIT_MIN);
                            $deadlineIso = $deadline->toIso8601String();
                            $editable = now()->lt($deadline);
                        }
                        if ($registro && ($registro->fecha_firma || $registro->firma_sig)) {
                            $firmado = true;
                        }

                        $documentos[] = [
                            'materia'            => $m->materia,
                            'programa'           => $m->programa,
                            'grupo'              => $m->grupo,
                            'unidad'             => 1,
                            'documento'          => $tipo,
                            'archivo'            => $plantilla,
                            'entregado'          => (bool) $registro,
                            'archivo_subido'     => $registro->archivo    ?? null,
                            'acuse'              => $registro->acuse_pdf  ?? null, // ya no se muestra en la vista
                            'acuse_lote'         => $acuseU,               // acuse general por unidad
                            'lote_id'            => $loteId,
                            'es_actual'          => $unidadActual === 1,
                            'editable'           => $editable,
                            'created_at'         => $createdAtIso,
                            'cierre_edicion_iso' => $editable ? $deadlineIso : null,
                            'firmado'            => $firmado,
                        ];
                    }
                }

                // Estándar por unidad
                foreach ($tipos as $tipo => $plantilla) {
                    $registro = DocumentoSubido::where([
                        ['user_id',        $user->id],
                        ['materia',        $m->materia],
                        ['grupo',          $m->grupo],
                        ['unidad',         $u],
                        ['tipo_documento', $tipo],
                    ])->first();

                    $createdAtIso     = $registro?->created_at?->toIso8601String();
                    $deadlineIso      = null;
                    $editable         = false;
                    $firmado          = false;

                    if ($registro && $registro->created_at) {
                        $deadline  = $registro->created_at->copy()->addMinutes($VENTANA_EDIT_MIN);
                        $deadlineIso = $deadline->toIso8601String();
                        $editable = now()->lt($deadline);
                    }
                    if ($registro && ($registro->fecha_firma || $registro->firma_sig)) {
                        $firmado = true;
                    }

                    $documentos[] = [
                        'materia'            => $m->materia,
                        'programa'           => $m->programa,
                        'grupo'              => $m->grupo,
                        'unidad'             => $u,
                        'documento'          => $tipo,
                        'archivo'            => $plantilla,
                        'entregado'          => (bool) $registro,
                        'archivo_subido'     => $registro->archivo    ?? null,
                        'acuse'              => $registro->acuse_pdf  ?? null, // ya no se muestra en la vista
                        'acuse_lote'         => $acuseU,
                        'lote_id'            => $loteId,
                        'es_actual'          => $u === $unidadActual,
                        'editable'           => $editable,
                        'created_at'         => $createdAtIso,
                        'cierre_edicion_iso' => $editable ? $deadlineIso : null,
                        'firmado'            => $firmado,
                    ];
                }

                // Final en última unidad
                if ($u === $totalUnidades) {
                    $tipoFinal = 'Reporte Cuatrimestral de la Evaluación Continua (SIGO)';

                    $registroFinal = DocumentoSubido::where([
                        ['user_id',        $user->id],
                        ['materia',        $m->materia],
                        ['grupo',          $m->grupo],
                        ['unidad',         $u],
                        ['tipo_documento', $tipoFinal],
                    ])->first();

                    $createdAtIso     = $registroFinal?->created_at?->toIso8601String();
                    $deadlineIso      = null;
                    $editable         = false;
                    $firmado          = false;

                    if ($registroFinal && $registroFinal->created_at) {
                        $deadline  = $registroFinal->created_at->copy()->addMinutes($VENTANA_EDIT_MIN);
                        $deadlineIso = $deadline->toIso8601String();
                        $editable = now()->lt($deadline);
                    }
                    if ($registroFinal && ($registroFinal->fecha_firma || $registroFinal->firma_sig)) {
                        $firmado = true;
                    }

                    $documentos[] = [
                        'materia'            => $m->materia,
                        'programa'           => $m->programa,
                        'grupo'              => $m->grupo,
                        'unidad'             => $u,
                        'documento'          => $tipoFinal,
                        'archivo'            => null,
                        'entregado'          => (bool) $registroFinal,
                        'archivo_subido'     => $registroFinal->archivo   ?? null,
                        'acuse'              => $registroFinal->acuse_pdf ?? null,
                        'acuse_lote'         => $acuseU,
                        'lote_id'            => $loteId,
                        'es_actual'          => $u === $unidadActual,
                        'editable'           => $editable,
                        'created_at'         => $createdAtIso,
                        'cierre_edicion_iso' => $editable ? $deadlineIso : null,
                        'firmado'            => $firmado,
                    ];
                }
            }
        }

        return view('modulos.gestion_academica', compact('documentos'));
    }
}
