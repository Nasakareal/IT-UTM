<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Submodulo;
use App\Models\SubmoduloArchivo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\SubmoduloUsuario;
use App\Models\DocumentoSubido;
use App\Models\CalificacionDocumento;

class RevisionAcademicaController extends Controller
{
    /* ------------------------------------------------------------------ */
    /* 1) SUBMÓDULOS – VISTA GENERAL (sin cambios)                        */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $usuario = auth()->user();

        // 1. Profesores del MISMO área y con categoría válida
        $areasUsuario = explode(',', $usuario->area);
        $categoriasValidas = ['Titular C', 'Titular B', 'Titular A', 'Asociado C'];

        // Obtener solo los teacher_id que sí tienen materias asignadas
        $teacherIdsConMaterias = DB::connection('cargahoraria')
            ->table('teacher_subjects')
            ->distinct()
            ->pluck('teacher_id')
            ->toArray();

        $profesores = User::whereNotNull('teacher_id')
            ->whereIn('teacher_id', $teacherIdsConMaterias)
            ->whereIn('categoria', $categoriasValidas)
            ->where(function ($query) use ($areasUsuario) {
                foreach ($areasUsuario as $area) {
                    $query->orWhereRaw('FIND_IN_SET(?, area)', [$area]);
                }
            })
            ->orderBy('nombres')
            ->get();


        // 2. Submódulos del módulo 5
        $query = Submodulo::whereHas('subsection', fn ($q) => $q->where('modulo_id', 5))
            ->with('subsection');

        if ($request->filled('subseccion_id')) {
            $query->where('subsection_id', $request->subseccion_id);
        }

        $submodulos = $query->get();

        // Orden personalizado de submódulos por el campo 'titulo'
        $ordenDeseado = [
            'Presentación del Tutor',
            '1er Tutoría Grupal',
            '2da Tutoría Grupal',
            '3er Tutoría Grupal',
            'Registro de Proyecto Institucional',
            'Informe Parcial',
            'Informe Global',
        ];

        $submodulos = $submodulos->sortBy(function ($submodulo) use ($ordenDeseado) {
            return array_search($submodulo->titulo, $ordenDeseado);
        })->values();

        // Subsecciones disponibles para el filtro
        $subseccionesDisponibles = \App\Models\Subsection::where('modulo_id', 5)
            ->orderBy('nombre')
            ->get();

        // 3. Archivos entregados
        $archivos = SubmoduloArchivo::whereIn('user_id', $profesores->pluck('id'))
            ->whereIn('submodulo_id', $submodulos->pluck('id'))
            ->get();

        $archivoMap = [];
        foreach ($archivos as $archivo) {
            $archivoMap[$archivo->user_id][$archivo->submodulo_id] = $archivo;
        }

        return view('revision_academica.index', compact(
            'profesores',
            'submodulos',
            'archivoMap',
            'subseccionesDisponibles'
        ));
    }

    /* ------------------------------------------------------------------ */
    /* 2) SOLO GESTIÓN ACADÉMICA – CON CALIFICACIONES                     */
    /* ------------------------------------------------------------------ */
    public function soloGestion(Request $request)
    {
        $usuario = auth()->user();

        // Áreas del subdirector (puede venir separadas por coma)
        $areas = collect(explode(',', (string)($usuario->area ?? '')))
            ->map(fn($a) => trim($a))
            ->filter()
            ->values();

        /* 2.1) Profesores del área */
        $teacherIds = DB::connection('cargahoraria')
            ->table('teacher_subjects as ts')
            ->join('subjects as s',  'ts.subject_id', '=', 's.subject_id')
            ->join('programs as p',  's.program_id',  '=', 'p.program_id')
            ->whereIn('p.area', $areas)
            ->pluck('ts.teacher_id')
            ->unique();

        $profesores = User::whereNotNull('teacher_id')
            ->whereIn('teacher_id', $teacherIds)
            ->get();

        /* 2.2) Filtros */
        $profesorId    = $request->profesor_id;
        $materiaFiltro = $request->materia;
        $unidadFiltro  = $request->unidad;
        $grupoFiltro   = $request->grupo;

        $profesorSeleccionado = $profesores->firstWhere('id', $profesorId);
        $documentos           = [];

        // Grupos disponibles por ÁREA (cuando no hay profesor seleccionado)
        $gruposArea = DB::connection('cargahoraria')
            ->table('teacher_subjects as ts')
            ->join('subjects as s',  'ts.subject_id', '=', 's.subject_id')
            ->join('programs as p',  's.program_id',  '=', 'p.program_id')
            ->join('groups as g',    'ts.group_id',   '=', 'g.group_id')
            ->whereIn('p.area', $areas)
            ->pluck('g.group_name')
            ->unique()
            ->sort()
            ->values();

        /* 2.3) Si hay profesor seleccionado, armar lista */
        if ($profesorSeleccionado) {

            /* Materias del profesor */
            $materias = DB::connection('cargahoraria')
                ->table('teacher_subjects as ts')
                ->join('subjects as s',  'ts.subject_id', '=', 's.subject_id')
                ->join('programs as p',  's.program_id',  '=', 'p.program_id')
                ->join('groups as g',    'ts.group_id',   '=', 'g.group_id')
                ->select(
                    's.subject_name as materia',
                    's.unidades',
                    'p.program_name as programa',
                    'g.group_name as grupo'
                )
                ->where('ts.teacher_id', $profesorSeleccionado->teacher_id)
                ->whereIn('p.area', $areas)
                ->groupBy('s.subject_name', 's.unidades', 'p.program_name', 'g.group_name')
                ->when($materiaFiltro, fn ($q) => $q->where('s.subject_name', $materiaFiltro))
                ->when($grupoFiltro,   fn ($q) => $q->where('g.group_name',   $grupoFiltro))
                ->get();

            /* Cuatrimestre activo (para unidad actual) */
            $hoy     = now();
            $cuatri  = DB::table('cuatrimestres')
                ->whereDate('fecha_inicio', '<=', $hoy)
                ->whereDate('fecha_fin',   '>=', $hoy)
                ->first();

            $totalDias = $diasTranscurridos = 0;
            if ($cuatri) {
                $inicio            = Carbon::parse($cuatri->fecha_inicio);
                $fin               = Carbon::parse($cuatri->fecha_fin);
                $totalDias         = $inicio->diffInDays($fin) + 1;
                $diasTranscurridos = $inicio->diffInDays($hoy) + 1;
            }

            /* Tipos estándar */
            $tiposEstandar = [
                'Reporte de Evaluación Continua por Unidad de Aprendizaje (SIGO)' => null,
                'Informe de Estudiantes No Acreditados'                           => 'F-DA-GA-05 Informe de Estudiantes No Acreditados.xlsx',
                'Control de Asesorías'                                            => 'F-DA-GA-06 Control de Asesorías.xlsx',
            ];

            /* Recorrer materias y unidades */
            foreach ($materias as $m) {
                $totalUnidades = (int) $m->unidades;
                $diasPorUnidad = $totalUnidades ? ceil($totalDias / $totalUnidades) : 0;
                $unidadActual  = $diasPorUnidad ? min($totalUnidades, ceil($diasTranscurridos / $diasPorUnidad)) : 1;

                for ($u = 1; $u <= $totalUnidades; $u++) {

                    /* 5A) Documentos especiales – unidad 1 */
                    if ($u === 1) {
                        $especiales = [
                            'Presentación de la Asignatura' => 'F-DA-GA-01 Presentación de la asignatura.xlsx',
                            'Planeación didáctica'          => 'F-DA-GA-02 Planeación didáctica del programa de asignatura.docx',
                            'Seguimiento de la Planeación'  => 'F-DA-GA-03 Seguimiento de la Planeación Didáctica.xlsx',
                        ];

                        foreach ($especiales as $tipo => $plantilla) {
                            if ($unidadFiltro && (int)$unidadFiltro !== 1) continue;

                            $registro = DocumentoSubido::where([
                                ['user_id',        $profesorSeleccionado->id],
                                ['materia',        $m->materia],
                                ['grupo',          $m->grupo],
                                ['unidad',         1],
                                ['tipo_documento', $tipo],
                            ])->first();

                            $calificacion = $registro
                                ? CalificacionDocumento::where('documento_id', $registro->id)
                                    ->where('evaluador_id', auth()->id())
                                    ->value('calificacion')
                                : null;

                            $firmado   = $registro && !is_null($registro->fecha_firma);
                            $modoFirma = $firmado ? ($registro->lote_id || $registro->firma_sig ? 'lote' : 'individual') : null;
                            $acusePdf  = $registro->acuse_pdf ?? null;

                            $documentos[] = [
                                'materia'         => $m->materia,
                                'programa'        => $m->programa,
                                'grupo'           => $m->grupo,
                                'unidad'          => 1,
                                'tipo_documento'  => $tipo,
                                'created_at'      => $registro->created_at ?? null,
                                'plantilla'       => $plantilla,
                                'entregado'       => (bool) $registro,
                                'archivo_subido'  => $registro->archivo ?? null,
                                'id'              => $registro->id ?? null,
                                'mi_calificacion' => $calificacion,
                                'es_actual'       => ($u === $unidadActual),
                                'firmado'         => $firmado,
                                'modo_firma'      => $modoFirma,
                                'acuse_pdf'       => $acusePdf,
                            ];
                        }
                    }

                    /* 5B) Documentos estándar */
                    foreach ($tiposEstandar as $tipo => $plantilla) {
                        if ($unidadFiltro && (int)$unidadFiltro !== $u) continue;

                        $registro = DocumentoSubido::where([
                            ['user_id',        $profesorSeleccionado->id],
                            ['materia',        $m->materia],
                            ['grupo',          $m->grupo],
                            ['unidad',         $u],
                            ['tipo_documento', $tipo],
                        ])->first();

                        $calificacion = $registro
                            ? CalificacionDocumento::where('documento_id', $registro->id)
                                ->where('evaluador_id', auth()->id())
                                ->value('calificacion')
                            : null;

                        $firmado   = $registro && !is_null($registro->fecha_firma);
                        $modo_firma = $firmado ? ($registro->lote_id || $registro->firma_sig ? 'lote' : 'individual') : null;
                        $acusePdf  = $registro->acuse_pdf ?? null;

                        $documentos[] = [
                            'materia'         => $m->materia,
                            'programa'        => $m->programa,
                            'grupo'           => $m->grupo,
                            'unidad'          => $u,
                            'tipo_documento'  => $tipo,
                            'created_at'      => $registro->created_at ?? null,
                            'plantilla'       => $plantilla,
                            'entregado'       => (bool) $registro,
                            'archivo_subido'  => $registro->archivo ?? null,
                            'id'              => $registro->id ?? null,
                            'mi_calificacion' => $calificacion,
                            'es_actual'       => ($u === $unidadActual),
                            'firmado'         => $firmado,
                            'modo_firma'      => $modo_firma,
                            'acuse_pdf'       => $acusePdf,
                        ];
                    }

                    /* 5C) Documento final */
                    if ($u === $totalUnidades) {
                        $tipoFinal = 'Reporte Cuatrimestral de la Evaluación Continua (SIGO)';
                        if ($unidadFiltro && (int)$unidadFiltro !== $u) continue;

                        $registroFinal = DocumentoSubido::where([
                            ['user_id',        $profesorSeleccionado->id],
                            ['materia',        $m->materia],
                            ['grupo',          $m->grupo],
                            ['unidad',         $u],
                            ['tipo_documento', $tipoFinal],
                        ])->first();

                        $califFinal = $registroFinal
                            ? CalificacionDocumento::where('documento_id', $registroFinal->id)
                                ->where('evaluador_id', auth()->id())
                                ->value('calificacion')
                            : null;

                        $firmado   = $registroFinal && !is_null($registroFinal->fecha_firma);
                        $modo_firma = $firmado ? ($registroFinal->lote_id || $registroFinal->firma_sig ? 'lote' : 'individual') : null;
                        $acusePdf  = $registroFinal->acuse_pdf ?? null;

                        $documentos[] = [
                            'materia'         => $m->materia,
                            'programa'        => $m->programa,
                            'grupo'           => $m->grupo,
                            'unidad'          => $u,
                            'tipo_documento'  => $tipoFinal,
                            'created_at'      => $registroFinal->created_at ?? null,
                            'plantilla'       => null,
                            'entregado'       => (bool) $registroFinal,
                            'archivo_subido'  => $registroFinal->archivo ?? null,
                            'id'              => $registroFinal->id ?? null,
                            'mi_calificacion' => $califFinal,
                            'es_actual'       => ($u === $unidadActual),
                            'firmado'         => $firmado,
                            'modo_firma'      => $modo_firma,
                            'acuse_pdf'       => $acusePdf,
                        ];
                    }
                }
            }
        }

        /* 2.4) Opciones de filtros */
        $materiasDisponibles = collect($documentos)->pluck('materia')->unique()->sort()->values();
        $unidadesDisponibles = collect($documentos)->pluck('unidad')->unique()->sort()->values();

        // Si hay profesor seleccionado, grupos desde sus documentos; si no, grupos por ÁREA
        $gruposDisponibles = $profesorSeleccionado
            ? collect($documentos)->pluck('grupo')->unique()->sort()->values()
            : $gruposArea;

        return view('revision_academica.solo_gestion', [
            'profesores'           => $profesores,
            'profesorSeleccionado' => $profesorSeleccionado,
            'materiasDisponibles'  => $materiasDisponibles,
            'unidadesDisponibles'  => $unidadesDisponibles,
            'documentos'           => $documentos,
            'gruposDisponibles'    => $gruposDisponibles,
        ]);
    }


    /* ------------------------------------------------------------------ */
    /* 3) ELIMINAR ARCHIVO – SIN CAMBIOS                                  */
    /* ------------------------------------------------------------------ */
    public function eliminarArchivo($id)
    {
        $archivo = SubmoduloArchivo::findOrFail($id);

        if ($archivo->ruta && \Storage::disk('public')->exists($archivo->ruta)) {
            \Storage::disk('public')->delete($archivo->ruta);
        }

        $archivo->delete();

        SubmoduloUsuario::where('user_id', $archivo->user_id)
            ->where('submodulo_id', $archivo->submodulo_id)
            ->delete();

        return back()->with('success', 'El archivo ha sido eliminado correctamente.');
    }

    public function eliminarUno(Request $request)
    {
        $doc = DocumentoSubido::where([
            ['user_id',        $request->user_id],
            ['materia',        $request->materia],
            ['grupo',          $request->grupo],
            ['unidad',         $request->unidad],
            ['tipo_documento', $request->tipo_documento],
        ])->first();

        if ($doc) {
            if ($doc->archivo && \Storage::disk('public')->exists($doc->archivo)) {
                \Storage::disk('public')->delete($doc->archivo);
            }
            $doc->delete();
        }

        return back()->with('success', 'Documento eliminado correctamente.');
    }
}
