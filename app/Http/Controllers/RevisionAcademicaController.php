<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Submodulo;
use App\Models\SubmoduloArchivo;
use App\Models\Subsection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\SubmoduloUsuario;
use App\Models\DocumentoSubido;


class RevisionAcademicaController extends Controller
{
    public function index(Request $request)
    {
        $usuario = auth()->user();

        // 1. Profesores del MISMO área
        $areasUsuario = explode(',', $usuario->area);

        $profesores = User::whereNotNull('teacher_id')
            ->where(function ($query) use ($areasUsuario) {
                foreach ($areasUsuario as $area) {
                    $query->orWhereRaw('FIND_IN_SET(?, area)', [$area]);
                }
            })
            ->get();


        // 2. Submódulos dentro del módulo 5 (filtrados por subsección si aplica)
        $query = Submodulo::whereHas('subsection', function ($q) {
            $q->where('modulo_id', 5); // Solo Gestión Académica
        })->with('subsection');

        if ($request->filled('subseccion_id')) {
            $query->where('subsection_id', $request->subseccion_id);
        }

        $submodulos = $query->get();

        // 3. Submódulos agrupados por subsección (para el combo)
        $subseccionesDisponibles = \App\Models\Subsection::where('modulo_id', 5)->get();

        // 4. Archivos entregados
        $archivos = SubmoduloArchivo::whereIn('user_id', $profesores->pluck('id'))
            ->whereIn('submodulo_id', $submodulos->pluck('id'))
            ->get();

        $archivoMap = [];
        foreach ($archivos as $archivo) {
            $archivoMap[$archivo->user_id][$archivo->submodulo_id] = $archivo;
        }

        return view('revision_academica.index', compact('profesores', 'submodulos', 'archivoMap', 'subseccionesDisponibles'));
    }

    public function soloGestion(Request $request)
    {
        $usuario = auth()->user();

        // 1) Obtener todos los profesores del área del usuario
        $areasUsuario = explode(',', $usuario->area);

        $profesores = User::whereNotNull('teacher_id')
            ->where(function ($query) use ($areasUsuario) {
                foreach ($areasUsuario as $area) {
                    $query->orWhereRaw('FIND_IN_SET(?, area)', [$area]);
                }
            })
            ->get();


        // 2) Leer filtros (profesor, materia, unidad) desde la query string
        $profesorId     = $request->input('profesor_id');
        $materiaFiltro  = $request->input('materia');
        $unidadFiltro   = $request->input('unidad');

        // 3) Determinar el profesor seleccionado (si existe)
        $profesorSeleccionado = $profesores->firstWhere('id', $profesorId);

        // 4) Preparar el arreglo de resultados
        $documentos = [];

        if ($profesorSeleccionado) {
            // 4A) Consultar las materias que imparte este profesor
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
                ->where('ts.teacher_id', $profesorSeleccionado->teacher_id)
                ->groupBy('s.subject_name', 's.unidades', 'p.program_name', 'g.group_name')
                ->get();

            // 4B) Filtro por materia: si viene materiaFiltro, filtrar la colección $materias
            if ($materiaFiltro) {
                $materias = $materias->where('materia', $materiaFiltro)->values();
            }

            // 4C) Obtener cuatrimestre activo (para calcular unidad actual)
            $hoy = now();
            $cuatri = DB::table('cuatrimestres')
                ->whereDate('fecha_inicio', '<=', $hoy)
                ->whereDate('fecha_fin', '>=', $hoy)
                ->first();

            if ($cuatri) {
                $inicio = Carbon::parse($cuatri->fecha_inicio);
                $fin = Carbon::parse($cuatri->fecha_fin);
                $totalDias = $inicio->diffInDays($fin) + 1;
                $diasTranscurridos = $inicio->diffInDays($hoy) + 1;
            }

            // 4D) Tipos de documento “estándar”
            $tiposEstandar = [
                'Reporte de Evaluación Continua por Unidad de Aprendizaje (SIGO)' => null,
                'Informe de Estudiantes No Acreditados'                           => 'F-DA-GA-05 Informe de Estudiantes No Acreditados.xlsx',
                'Control de Asesorías'                                            => 'F-DA-GA-06 Control de Asesorías.xlsx',
            ];

            foreach ($materias as $m) {
                // 5) Calcular dinámica de unidades
                $totalUnidades = $m->unidades;
                $unidadActual = 1;
                if (isset($totalDias)) {
                    $diasPorUnidad = (int) ceil($totalDias / $totalUnidades);
                    $unidadActual = min($totalUnidades, (int) ceil($diasTranscurridos / $diasPorUnidad));
                }

                for ($u = 1; $u <= $totalUnidades; $u++) {
                    // 5A) Documentos especiales para la unidad 1
                    if ($u === 1) {
                        $documentosEspeciales = [
                            'Presentación de la Asignatura'     => 'F-DA-GA-01 Presentación de la asignatura.xlsx',
                            'Planeación didáctica'             => 'F-DA-GA-02 Planeación didáctica del programa de asignatura.docx',
                            'Seguimiento de la Planeación'     => 'F-DA-GA-03 Seguimiento de la Planeación Didáctica.xlsx',
                        ];

                        foreach ($documentosEspeciales as $tipo => $plantilla) {
                            // Filtrar por unidad: si $unidadFiltro existe y es distinta de 1, saltar
                            if ($unidadFiltro && $unidadFiltro != 1) {
                                continue;
                            }

                            $registro = DocumentoSubido::where([
                                ['user_id',        $profesorSeleccionado->id],
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
                                'tipo_documento' => $tipo,
                                'plantilla'      => $plantilla,
                                'entregado'      => (bool) $registro,
                                'archivo_subido' => $registro->archivo ?? null,
                                // Eliminamos acceso a $registro->unidad para evitar error
                                'es_actual'      => ($u === $unidadActual),
                            ];
                        }
                    }

                    // 5B) Documentos estándar para cada unidad
                    foreach ($tiposEstandar as $tipo => $plantilla) {
                        // Filtrar por unidad: si $unidadFiltro existe y es distinta de $u, saltar
                        if ($unidadFiltro && $unidadFiltro != $u) {
                            continue;
                        }

                        $registro = DocumentoSubido::where([
                            ['user_id',        $profesorSeleccionado->id],
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
                            'tipo_documento' => $tipo,
                            'plantilla'      => $plantilla,
                            'entregado'      => (bool) $registro,
                            'archivo_subido' => $registro->archivo ?? null,
                            // Si necesitas fecha límite, usa $registro->fecha_limite si existe
                            'es_actual'      => ($u === $unidadActual),
                        ];
                    }

                    // 5C) Documento final en la última unidad
                    if ($u === $totalUnidades) {
                        $tipoFinal = 'Reporte Cuatrimestral de la Evaluación Continua (SIGO)';

                        // Filtrar por unidad: si $unidadFiltro existe y es distinta de $u, saltar
                        if (!($unidadFiltro && $unidadFiltro != $u)) {
                            $registroFinal = DocumentoSubido::where([
                                ['user_id',        $profesorSeleccionado->id],
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
                                'tipo_documento' => $tipoFinal,
                                'plantilla'      => null,
                                'entregado'      => (bool) $registroFinal,
                                'archivo_subido' => $registroFinal->archivo ?? null,
                                'es_actual'      => ($u === $unidadActual),
                            ];
                        }
                    }
                }
            }
        }

        // 6) Obtener listas únicas de materias y unidades para los <select> de filtro
        $materiasDisponibles = collect($documentos)
            ->pluck('materia')
            ->unique()
            ->sort()
            ->values();

        $unidadesDisponibles = collect($documentos)
            ->pluck('unidad')
            ->unique()
            ->sort()
            ->values();

        // 7) Devolver la vista con los datos completos
        return view('revision_academica.solo_gestion', [
            'profesores'           => $profesores,
            'profesorSeleccionado' => $profesorSeleccionado,
            'materiasDisponibles'  => $materiasDisponibles,
            'unidadesDisponibles'  => $unidadesDisponibles,
            'documentos'           => $documentos,
        ]);
    }

    public function eliminarArchivo($id)
    {
        $archivo = SubmoduloArchivo::findOrFail($id);

        // Elimina archivo físico si existe
        if ($archivo->ruta && \Storage::disk('public')->exists($archivo->ruta)) {
            \Storage::disk('public')->delete($archivo->ruta);
        }

        // Elimina archivo en base de datos
        $archivo->delete();

        // También elimina o cambia el estatus en la tabla submodulo_usuario
        SubmoduloUsuario::where('user_id', $archivo->user_id)
            ->where('submodulo_id', $archivo->submodulo_id)
            ->delete(); // ❗️O usa ->update(['estatus' => 'Pendiente']);

        return back()->with('success', 'El archivo ha sido eliminado correctamente.');
    }


}
