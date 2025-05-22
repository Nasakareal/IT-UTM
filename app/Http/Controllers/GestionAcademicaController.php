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
        $documentos = [];

        if (!$user->teacher_id) {
            return back()->with('error', 'Este usuario no tiene asignado un teacher_id.');
        }

        $materias = DB::connection('cargahoraria')
            ->table('teacher_subjects as ts')
            ->join('subjects as s', 'ts.subject_id', '=', 's.subject_id')
            ->select('s.subject_name as materia', 's.unidades')
            ->where('ts.teacher_id', $user->teacher_id)
            ->groupBy('s.subject_name', 's.unidades')
            ->get();

        if ($materias->isEmpty()) {
            return back()->with('error', 'No se encontraron materias asignadas.');
        }

        $hoy = Carbon::now();
        $cuatrimestre = DB::table('cuatrimestres')
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy)
            ->first();

        if (!$cuatrimestre) {
            return back()->with('error', 'No hay cuatrimestre activo configurado.');
        }

        $inicioCuatrimestre = Carbon::parse($cuatrimestre->fecha_inicio);
        $finCuatrimestre = Carbon::parse($cuatrimestre->fecha_fin);
        $duracionTotalDias = $inicioCuatrimestre->diffInDays($finCuatrimestre) + 1;
        $diasTranscurridos = $inicioCuatrimestre->diffInDays($hoy) + 1;

        $tipos = [
            'Planeación didáctica'         => 'F-DA-GA-02 Planeación didáctica del programa de asignatura.docx',
            'Seguimiento de la Planeación' => 'F-DA-GA-03 Seguimiento de la Planeación Didáctica.xlsx',
            'Informe de Estudiantes'       => 'F-DA-GA-05 Informe de Estudiantes No Acreditados.xlsx',
            'Control de Asesorías'         => 'F-DA-GA-06 Control de Asesorías.xlsx',
        ];

        foreach ($materias as $materia) {
            $totalUnidades = $materia->unidades;
            $diasPorUnidad = (int) ceil($duracionTotalDias / $totalUnidades);
            $unidadActual = (int) ceil($diasTranscurridos / $diasPorUnidad);
            if ($unidadActual > $totalUnidades) {
                $unidadActual = $totalUnidades;
            }

            foreach ($tipos as $tipo => $archivo) {
                $registro = DocumentoSubido::where('user_id', $user->id)
                    ->where('materia', $materia->materia)
                    ->where('unidad', $unidadActual)
                    ->where('tipo_documento', $tipo)
                    ->first();

                $documentos[] = [
                    'materia'        => $materia->materia,
                    'unidad'         => $unidadActual,
                    'documento'      => $tipo,
                    'archivo'        => $archivo,
                    'entregado'      => $registro ? true : false,
                    'archivo_subido' => $registro->archivo ?? null,
                    'acuse'          => $registro->acuse_pdf ?? null,
                ];
            }

            // Documento extra solo para unidad 1
            if ($unidadActual === 1) {
                $registro = DocumentoSubido::where('user_id', $user->id)
                    ->where('materia', $materia->materia)
                    ->where('unidad', 1)
                    ->where('tipo_documento', 'Presentación de la Asignatura')
                    ->first();

                $documentos[] = [
                    'materia'        => $materia->materia,
                    'unidad'         => 1,
                    'documento'      => 'Presentación de la Asignatura',
                    'archivo'        => 'F-DA-GA-01 Presentación de la asignatura.xlsx',
                    'entregado'      => $registro ? true : false,
                    'archivo_subido' => $registro->archivo ?? null,
                    'acuse'          => $registro->acuse_pdf ?? null,
                ];
            }
        }

        return view('modulos.gestion_academica', compact('documentos'));
    }
}
