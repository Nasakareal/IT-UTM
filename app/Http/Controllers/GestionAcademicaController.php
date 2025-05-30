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

        // 1) Materias del docente
        $materias = DB::connection('cargahoraria')
            ->table('teacher_subjects as ts')
            ->join('subjects as s', 'ts.subject_id', '=', 's.subject_id')
            ->join('programs as p', 's.program_id', '=', 'p.program_id')
            ->join('groups as g', 'ts.group_id', '=', 'g.group_id')
            ->select(
                's.subject_name as materia',
                's.unidades',
                'p.program_name as programa',
                'g.group_name as grupo'
            )
            ->where('ts.teacher_id', $user->teacher_id)
            ->groupBy('s.subject_name', 's.unidades', 'p.program_name', 'g.group_name')
            ->get();

        if ($materias->isEmpty()) {
            return back()->with('error', 'No se encontraron materias asignadas.');
        }

        // 2) Cuatrimestre activo
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

        // 3) Tipos de documento
        $tipos = [
            'Reporte de Evaluación Continua por Unidad de Aprendizaje (SIGO)' => null,
            'Informe de Estudiantes No Acreditados'                           => 'F-DA-GA-05 Informe de Estudiantes No Acreditados.xlsx',
            'Control de Asesorías'                                            => 'F-DA-GA-06 Control de Asesorías.xlsx',
        ];

        $documentos = [];

        // 4) Generar entradas por materia y unidad
        foreach ($materias as $m) {
            $totalUnidades = $m->unidades;
            $diasPorUnidad = (int) ceil($totalDias / $totalUnidades);
            $unidadActual  = min($totalUnidades, (int) ceil($diasTranscurridos / $diasPorUnidad));

            for ($u = 1; $u <= $totalUnidades; $u++) {

                // Documentos especiales en unidad 1
                if ($u === 1) {
                    $documentosEspeciales = [
                        'Presentación de la Asignatura'     => 'F-DA-GA-01 Presentación de la asignatura.xlsx',
                        'Planeación didáctica'             => 'F-DA-GA-02 Planeación didáctica del programa de asignatura.docx',
                        'Seguimiento de la Planeación'     => 'F-DA-GA-03 Seguimiento de la Planeación Didáctica.xlsx',
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
                    ];
                }
            }
        }

        // 5) Pasamos todo a la vista
        return view('modulos.gestion_academica', compact('documentos'));
    }
}
