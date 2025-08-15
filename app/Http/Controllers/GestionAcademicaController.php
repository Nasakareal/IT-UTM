<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\DocumentoSubido;

class GestionAcademicaController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (! $user->teacher_id) {
            return back()->with('error', 'Este usuario no tiene asignado un teacher_id.');
        }

        // 1) Cuatrimestre activo (lo moví arriba para filtrar el snapshot por cuatrimestre)
        $hoy    = Carbon::now();
        $cuatri = DB::table('cuatrimestres')
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin',   '>=', $hoy)
            ->first();

        if (! $cuatri) {
            return back()->with('error', 'No hay cuatrimestre activo configurado.');
        }

        $inicio            = Carbon::parse($cuatri->fecha_inicio);
        $fin               = Carbon::parse($cuatri->fecha_fin);
        $totalDias         = $inicio->diffInDays($fin) + 1;
        $diasTranscurridos = $inicio->diffInDays($hoy) + 1;

        // 2) Materias del docente — PRIMERO desde el SNAPSHOT local
        $materias = DB::table('materias_docentes_snapshots')
            ->select('materia','unidades','programa','grupo')
            ->where('teacher_id', $user->teacher_id)
            ->when(isset($cuatri->id), fn($q) => $q->where('cuatrimestre_id', $cuatri->id))
            ->orderBy('programa')
            ->orderBy('materia')
            ->orderBy('grupo')
            ->get();

        // 2b) Si el snapshot está vacío, intenta leer de cargahoraria y (opcional) grabar snapshot al vuelo
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

            // (Opcional pero recomendado) Persistir snapshot para no depender más de cargahoraria
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

            // usa las remotas para seguir con la vista actual
            $materias = $remotas->map(fn($r) => (object)[
                'materia'  => $r->materia,
                'unidades' => (int)$r->unidades,
                'programa' => $r->programa,
                'grupo'    => $r->grupo,
            ]);
        }

        // 3) Tipos de documento (igual que lo tenías)
        $tipos = [
            'Reporte de Evaluación Continua por Unidad de Aprendizaje (SIGO)' => null,
            'Informe de Estudiantes No Acreditados'                           => 'F-DA-GA-05 Informe de Estudiantes No Acreditados.xlsx',
            'Control de Asesorías'                                            => 'F-DA-GA-06 Control de Asesorías.xlsx',
            'Seguimiento de la Planeación'                                    => 'F-DA-GA-03 Seguimiento de la Planeación Didáctica.xlsx',
        ];

        $documentos = [];

        // 4) Generar entradas por materia y unidad (idéntico)
        foreach ($materias as $m) {
            $totalUnidades = (int)$m->unidades;
            $diasPorUnidad = (int) ceil($totalDias / max(1, $totalUnidades));
            $unidadActual  = min($totalUnidades, (int) ceil($diasTranscurridos / max(1, $diasPorUnidad)));

            for ($u = 1; $u <= $totalUnidades; $u++) {

                // Documentos especiales en unidad 1
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

                        $documentos[] = [
                            'materia'        => $m->materia,
                            'programa'       => $m->programa,
                            'grupo'          => $m->grupo,
                            'unidad'         => 1,
                            'documento'      => $tipo,
                            'archivo'        => $plantilla,
                            'entregado'      => (bool) $registro,
                            'archivo_subido' => $registro->archivo    ?? null,
                            'acuse'          => $registro->acuse_pdf  ?? null,
                            'es_actual'      => $unidadActual === 1,
                            'editable'       => $registro && $registro->created_at && $registro->created_at->gt(now()->subMinutes(30)),
                        ];
                    }
                }

                // Documentos estándar por unidad
                foreach ($tipos as $tipo => $plantilla) {
                    $registro = DocumentoSubido::where([
                        ['user_id',        $user->id],
                        ['materia',        $m->materia],
                        ['grupo',          $m->grupo],
                        ['unidad',         $u],
                        ['tipo_documento', $tipo],
                    ])->first();

                    $documentos[] = [
                        'materia'        => $m->materia,
                        'programa'       => $m->programa,
                        'grupo'          => $m->grupo,
                        'unidad'         => $u,
                        'documento'      => $tipo,
                        'archivo'        => $plantilla,
                        'entregado'      => (bool) $registro,
                        'archivo_subido' => $registro->archivo    ?? null,
                        'acuse'          => $registro->acuse_pdf  ?? null,
                        'es_actual'      => $u === $unidadActual,
                        'editable'       => $registro && $registro->created_at && $registro->created_at->gt(now()->subMinutes(30)),
                    ];
                }

                // Documento final en última unidad
                if ($u === $totalUnidades) {
                    $tipoFinal = 'Reporte Cuatrimestral de la Evaluación Continua (SIGO)';

                    $registroFinal = DocumentoSubido::where([
                        ['user_id',        $user->id],
                        ['materia',        $m->materia],
                        ['grupo',          $m->grupo],
                        ['unidad',         $u],
                        ['tipo_documento', $tipoFinal],
                    ])->first();

                    $documentos[] = [
                        'materia'        => $m->materia,
                        'programa'       => $m->programa,
                        'grupo'          => $m->grupo,
                        'unidad'         => $u,
                        'documento'      => $tipoFinal,
                        'archivo'        => null,
                        'entregado'      => (bool) $registroFinal,
                        'archivo_subido' => $registroFinal->archivo   ?? null,
                        'acuse'          => $registroFinal->acuse_pdf ?? null,
                        'es_actual'      => $u === $unidadActual,
                        'editable'       => $registroFinal && $registroFinal->created_at && $registroFinal->created_at->gt(now()->subMinutes(30)),
                    ];
                }
            }
        }

        // 5) Pasamos todo a la vista
        return view('modulos.gestion_academica', compact('documentos'));
    }
}
