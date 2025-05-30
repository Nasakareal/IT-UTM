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
        $hoy = Carbon::now();
        $cuatri = DB::table('cuatrimestres')
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin',   '>=', $hoy)
            ->first();

        if (! $cuatri) {
            return back()->with('error', 'No hay cuatrimestre activo configurado.');
        }

        $inicio  = Carbon::parse($cuatri->fecha_inicio);
        $fin     = Carbon::parse($cuatri->fecha_fin);
        $totalDias       = $inicio->diffInDays($fin) + 1;
        $diasTranscurridos = $inicio->diffInDays($hoy) + 1;

        // 3) Tipos de documento
        $tipos = [
            'Planeación didáctica'         => 'F-DA-GA-02 Planeación didáctica del programa de asignatura.docx',
            'Seguimiento de la Planeación' => 'F-DA-GA-03 Seguimiento de la Planeación Didáctica.xlsx',
            'Informe de Estudiantes'       => 'F-DA-GA-05 Informe de Estudiantes No Acreditados.xlsx',
            'Control de Asesorías'         => 'F-DA-GA-06 Control de Asesorías.xlsx',
        ];

        $documentos = [];

        // 4) Generar entradas por materia y unidad
        foreach ($materias as $m) {
            $totalUnidades = $m->unidades;
            $diasPorUnidad = (int) ceil($totalDias / $totalUnidades);
            $unidadActual  = min($totalUnidades, (int) ceil($diasTranscurridos / $diasPorUnidad));

            for ($u = 1; $u <= $totalUnidades; $u++) {
                // cada tipo para la unidad $u
                foreach ($tipos as $tipo => $plantilla) {
                    $registro = DocumentoSubido::where([
                        ['user_id', '=',        $user->id],
                        ['materia', '=',        $m->materia],
                        ['unidad',  '=',        $u],
                        ['tipo_documento', '=', $tipo],
                    ])->first();

                    $documentos[] = [
                        'materia'        => $m->materia,
                        'programa'       => $m->programa,
                        'grupo'          => $m->grupo,
                        'unidad'         => $u,
                        'documento'      => $tipo,
                        'archivo'        => $plantilla,
                        'entregado'      => (bool) $registro,
                        'archivo_subido' => $registro->archivo ?? null,
                        'acuse'          => $registro->acuse_pdf ?? null,
                        'es_actual'      => $u === $unidadActual,
                    ];
                }

                // extra: Presentación de la Asignatura en unidad 1
                if ($u === 1) {
                    $tipoExtra    = 'Presentación de la Asignatura';
                    $plantillaExtra = 'F-DA-GA-01 Presentación de la asignatura.xlsx';

                    $registroExtra = DocumentoSubido::where([
                        ['user_id',        '=', $user->id],
                        ['materia',        '=', $m->materia],
                        ['unidad',         '=', 1],
                        ['tipo_documento', '=', $tipoExtra],
                    ])->first();

                    $documentos[] = [
                        'materia'        => $m->materia,
                        'programa'       => $m->programa,
                        'grupo'          => $m->grupo,
                        'unidad'         => 1,
                        'documento'      => $tipoExtra,
                        'archivo'        => $plantillaExtra,
                        'entregado'      => (bool) $registroExtra,
                        'archivo_subido' => $registroExtra->archivo ?? null,
                        'acuse'          => $registroExtra->acuse_pdf ?? null,
                        'es_actual'      => $unidadActual === 1,
                    ];
                }
            }
        }

        // 5) Pasamos todo a la vista
        return view('modulos.gestion_academica', compact('documentos'));
    }
}
