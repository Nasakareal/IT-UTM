<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Submodulo;
use App\Models\SubmoduloArchivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\SubmoduloUsuario;
use App\Models\DocumentoSubido;
use App\Models\CalificacionDocumento;
use App\Models\CalificacionSubmoduloArchivo;

class RevisionAcademicaController extends Controller
{
    /* ------------------------------------------------------------------ */
    /* 1) SUBMÓDULOS – VISTA GENERAL (AGREGADO: mapas de calificaciones)  */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $usuario = auth()->user();

        /* 1) Áreas del usuario y categorías válidas */
        $areasUsuario = collect(explode(',', (string)($usuario->area ?? '')))
            ->map(fn($a) => trim($a))
            ->filter()
            ->values()
            ->all();
        $categoriasValidas = ['Titular C', 'Titular B', 'Titular A', 'Asociado C'];

        /* 2) Profesores base por área (sin candado de carga viva) */
        $baseQuery = User::whereNotNull('teacher_id')
            ->when(!empty($areasUsuario), function ($q) use ($areasUsuario) {
                $q->where(function ($qq) use ($areasUsuario) {
                    foreach ($areasUsuario as $area) {
                        $qq->orWhereRaw('FIND_IN_SET(?, area)', [$area]);
                    }
                });
            });

        $profesores = (clone $baseQuery)
            ->whereIn('categoria', $categoriasValidas)
            ->orderBy('nombres')
            ->get();

        if ($profesores->isEmpty()) {
            // Fallback suave: si por categoría quedó vacío, quitamos categoría (¡pero seguimos en snapshot-only!)
            $profesores = (clone $baseQuery)->orderBy('nombres')->get();
        }

        /* 3) Submódulos del módulo 5 (con filtro opcional) */
        $query = Submodulo::whereHas('subsection', fn ($q) => $q->where('modulo_id', 5))
            ->with('subsection');

        if ($request->filled('subseccion_id')) {
            $query->where('subsection_id', $request->subseccion_id);
        }
        $submodulos = $query->get();

        $ordenDeseado = [
            'Presentación del Tutor',
            '1er Tutoría Grupal',
            '2da Tutoría Grupal',
            '3er Tutoría Grupal',
            'Registro de Proyecto Institucional',
            'Informe Parcial',
            'Informe Global',
        ];
        $submodulos = $submodulos->sortBy(fn($s) => array_search($s->titulo, $ordenDeseado))->values();

        $subseccionesDisponibles = \App\Models\Subsection::where('modulo_id', 5)
            ->orderBy('nombre')
            ->get();

        /* 4) SNAPSHOT ONLY: detectar conexión y leer snapshot */
        $snapConn = 'cargahoraria';
        if (!Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
            $snapConn = config('database.default', 'mysql');
        }

        $cuatrimestreActual = $request->input('quarter_name');
        if (!$cuatrimestreActual && Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
            $cuatrimestreActual = DB::connection($snapConn)
                ->table('materias_docentes_snapshots')
                ->orderBy('captured_at', 'desc')
                ->value('quarter_name');
        }

        $materiasPorDocente = [];
        $teacherIdsSnap = collect();

        if (Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
            $rowsSnapshot = DB::connection($snapConn)
                ->table('materias_docentes_snapshots')
                ->when(!empty($cuatrimestreActual), fn($q) => $q->where('quarter_name', $cuatrimestreActual))
                ->get([
                    'teacher_id','materia','grupo','programa','unidades',
                    'subject_id','group_id','program_id','quarter_name','captured_at','source'
                ]);

            // Limitar profesores a los que EXISTEN en snapshot
            $teacherIdsSnap = $rowsSnapshot->pluck('teacher_id')->unique()->values();
            if ($teacherIdsSnap->isNotEmpty()) {
                $profesores = $profesores->whereIn('teacher_id', $teacherIdsSnap->all())->values();
            } else {
                // Si no hay snapshot para ese quarter, deja vacíos (no usamos carga viva)
                $profesores = collect();
            }

            // Armar mapa materiasPorDocente exclusivamente desde snapshot
            foreach ($rowsSnapshot as $r) {
                $materiasPorDocente[$r->teacher_id][] = [
                    'materia'      => $r->materia,
                    'grupo'        => $r->grupo,
                    'programa'     => $r->programa,
                    'unidades'     => (int)$r->unidades,
                    'subject_id'   => (int)$r->subject_id,
                    'group_id'     => (int)$r->group_id,
                    'program_id'   => (int)$r->program_id,
                    'quarter_name' => $r->quarter_name,
                    'captured_at'  => $r->captured_at,
                    'source'       => $r->source,
                ];
            }
        } else {
            // No hay tabla snapshot en ninguna conexión conocida: no mostramos nada y avisamos en debug
            $profesores = collect();
        }

        /* 5) Archivos entregados SOLO de los profesores filtrados por snapshot */
        $archivos = SubmoduloArchivo::whereIn('user_id', $profesores->pluck('id'))
            ->whereIn('submodulo_id', $submodulos->pluck('id'))
            ->get();

        $archivoMap = [];
        foreach ($archivos as $archivo) {
            $archivoMap[$archivo->user_id][$archivo->submodulo_id] = $archivo;
        }

        /* 6) Calificaciones (mapas) */
        $idsVisibles = [];
        foreach ($archivoMap as $byUser) {
            foreach ($byUser as $a) {
                if (!empty($a->id)) $idsVisibles[] = (int)$a->id;
            }
        }
        $idsVisibles = array_values(array_unique($idsVisibles));

        $misCalifsMap = [];
        if (!empty($idsVisibles)) {
            $misCalifsMap = CalificacionSubmoduloArchivo::where('evaluador_id', auth()->id())
                ->whereIn('submodulo_archivo_id', $idsVisibles)
                ->pluck('calificacion', 'submodulo_archivo_id')
                ->toArray();
        }

        $promediosMap = [];
        if (!empty($idsVisibles)) {
            $promedios = CalificacionSubmoduloArchivo::select(
                    'submodulo_archivo_id',
                    DB::raw('ROUND(AVG(calificacion),2) as avg'),
                    DB::raw('COUNT(*) as n')
                )
                ->whereIn('submodulo_archivo_id', $idsVisibles)
                ->groupBy('submodulo_archivo_id')
                ->get();

            foreach ($promedios as $row) {
                $promediosMap[(int)$row->submodulo_archivo_id] = [
                    'avg' => (float)$row->avg,
                    'n'   => (int)$row->n,
                ];
            }
        }

        /* 7) Debug opcional */
        $debug = [
            'areasUsuario'          => $areasUsuario,
            'profesores_count'      => $profesores->count(),
            'submodulos_count'      => $submodulos->count(),
            'archivos_count'        => $archivos->count(),
            'idsVisibles_count'     => count($idsVisibles),
            'snap_connection'       => $snapConn,
            'has_snap_table'        => Schema::connection($snapConn)->hasTable('materias_docentes_snapshots'),
            'cuatrimestreActual'    => $cuatrimestreActual,
            'teacherIdsSnap_count'  => $teacherIdsSnap->count(),
        ];

        return view('revision_academica.index', compact(
            'profesores',
            'submodulos',
            'archivoMap',
            'subseccionesDisponibles',
            'misCalifsMap',
            'promediosMap',
            'materiasPorDocente',
            'cuatrimestreActual',
            'debug'
        ));
    }

    /* ------------------------------------------------------------------ */
    /* 2) SOLO GESTIÓN ACADÉMICA – CON CALIFICACIONES                     */
    /* ------------------------------------------------------------------ */
    public function soloGestion(Request $request)
    {
        $usuario = auth()->user();

        $areas = collect(explode(',', (string)($usuario->area ?? '')))
            ->map(fn($a) => trim($a))
            ->filter()
            ->values();

        // Conexión donde vive la snapshot
        $snapConn = 'cargahoraria';
        if (!Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
            $snapConn = config('database.default', 'mysql');
        }

        // Programas permitidos por área (desde cargahoraria.programs)
        $programIdsPermitidos = DB::connection('cargahoraria')
            ->table('programs')
            ->whereIn('area', $areas)
            ->pluck('program_id')
            ->all();

        // Cuatrimestre a usar (request o el más reciente de la snapshot)
        $quarter = $request->input('quarter_name');
        if (!$quarter && Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
            $quarter = DB::connection($snapConn)
                ->table('materias_docentes_snapshots')
                ->orderBy('captured_at', 'desc')
                ->value('quarter_name');
        }

        // Profesores desde snapshot (filtrando por área vía program_id y por quarter)
        $teacherIds = DB::connection($snapConn)
            ->table('materias_docentes_snapshots as mds')
            ->when(!empty($programIdsPermitidos), fn($q) => $q->whereIn('mds.program_id', $programIdsPermitidos))
            ->when($quarter, fn($q) => $q->where('mds.quarter_name', $quarter))
            ->distinct()
            ->pluck('mds.teacher_id')
            ->unique();

        $profesores = User::whereNotNull('teacher_id')
            ->whereIn('teacher_id', $teacherIds)
            ->orderBy('nombres')
            ->get();

        // Filtros de la vista
        $profesorId    = $request->profesor_id;   // users.id
        $materiaFiltro = $request->materia;
        $unidadFiltro  = $request->unidad;
        $grupoFiltro   = $request->grupo;

        $profesorSeleccionado = $profesores->firstWhere('id', $profesorId);
        $documentos = [];

        // Grupos del área desde la snapshot
        $gruposArea = DB::connection($snapConn)
            ->table('materias_docentes_snapshots as mds')
            ->when(!empty($programIdsPermitidos), fn($q) => $q->whereIn('mds.program_id', $programIdsPermitidos))
            ->when($quarter, fn($q) => $q->where('mds.quarter_name', $quarter))
            ->pluck('mds.grupo')
            ->unique()
            ->sort()
            ->values();

        if ($profesorSeleccionado) {
            // Materias del profesor desde snapshot (únicas por materia/unidades/programa/grupo)
            $materias = DB::connection($snapConn)
                ->table('materias_docentes_snapshots as mds')
                ->select('mds.materia', 'mds.unidades', 'mds.programa', 'mds.grupo')
                ->where('mds.teacher_id', $profesorSeleccionado->teacher_id)
                ->when(!empty($programIdsPermitidos), fn($q) => $q->whereIn('mds.program_id', $programIdsPermitidos))
                ->when($quarter, fn($q) => $q->where('mds.quarter_name', $quarter))
                ->when($materiaFiltro, fn($q) => $q->where('mds.materia', $materiaFiltro))
                ->when($grupoFiltro, fn($q) => $q->where('mds.grupo', $grupoFiltro))
                ->groupBy('mds.materia', 'mds.unidades', 'mds.programa', 'mds.grupo')
                ->get();

            // Cuatrimestre activo (para calcular unidad actual)
            $hoy    = now();
            $cuatri = DB::table('cuatrimestres')
                ->whereDate('fecha_inicio', '<=', $hoy)
                ->whereDate('fecha_fin', '>=', $hoy)
                ->first();

            $totalDias = $diasTranscurridos = 0;
            if ($cuatri) {
                $inicio            = Carbon::parse($cuatri->fecha_inicio);
                $fin               = Carbon::parse($cuatri->fecha_fin);
                $totalDias         = $inicio->diffInDays($fin) + 1;
                $diasTranscurridos = $inicio->diffInDays($hoy) + 1;
            }

            $tiposEstandar = [
                'Reporte de Evaluación Continua por Unidad de Aprendizaje (SIGO)' => null,
                'Informe de Estudiantes No Acreditados'                           => 'F-DA-GA-05 Informe de Estudiantes No Acreditados.xlsx',
                'Control de Asesorías'                                            => 'F-DA-GA-06 Control de Asesorías.xlsx',
                'Seguimiento de la Planeación'                                    => 'F-DA-GA-03 Seguimiento de la Planeación Didáctica.xlsx',
            ];

            foreach ($materias as $m) {
                $totalUnidades = (int) $m->unidades;
                $diasPorUnidad = $totalUnidades ? (int)ceil($totalDias / $totalUnidades) : 0;
                $unidadActual  = $diasPorUnidad ? min($totalUnidades, (int)ceil($diasTranscurridos / $diasPorUnidad)) : 1;

                for ($u = 1; $u <= $totalUnidades; $u++) {
                    if ($u === 1) {
                        $especiales = [
                            'Presentación de la Asignatura' => 'F-DA-GA-01 Presentación de la asignatura.xlsx',
                            'Planeación didáctica'          => 'F-DA-GA-02 Planeación didáctica del programa de asignatura.docx',
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

                            $calificacion = null;
                            if ($registro) {
                                $mi = CalificacionDocumento::where('documento_id', $registro->id)
                                    ->where('evaluador_id', auth()->id())
                                    ->value('calificacion');
                                if (is_null($mi)) {
                                    $mi = CalificacionDocumento::where('documento_id', $registro->id)
                                        ->orderByDesc('id')
                                        ->value('calificacion');
                                }
                                $calificacion = $mi;
                            }

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

                    foreach ($tiposEstandar as $tipo => $plantilla) {
                        if ($unidadFiltro && (int)$unidadFiltro !== $u) continue;

                        $registro = DocumentoSubido::where([
                            ['user_id',        $profesorSeleccionado->id],
                            ['materia',        $m->materia],
                            ['grupo',          $m->grupo],
                            ['unidad',         $u],
                            ['tipo_documento', $tipo],
                        ])->first();

                        $calificacion = null;
                        if ($registro) {
                            $mi = CalificacionDocumento::where('documento_id', $registro->id)
                                ->where('evaluador_id', auth()->id())
                                ->value('calificacion');
                            if (is_null($mi)) {
                                $mi = CalificacionDocumento::where('documento_id', $registro->id)
                                    ->orderByDesc('id')
                                    ->value('calificacion');
                            }
                            $calificacion = $mi;
                        }

                        $firmado    = $registro && !is_null($registro->fecha_firma);
                        $modo_firma = $firmado ? ($registro->lote_id || $registro->firma_sig ? 'lote' : 'individual') : null;
                        $acusePdf   = $registro->acuse_pdf ?? null;

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

                        $califFinal = null;
                        if ($registroFinal) {
                            $mi = CalificacionDocumento::where('documento_id', $registroFinal->id)
                                ->where('evaluador_id', auth()->id())
                                ->value('calificacion');
                            if (is_null($mi)) {
                                $mi = CalificacionDocumento::where('documento_id', $registroFinal->id)
                                    ->orderByDesc('id')
                                    ->value('calificacion');
                            }
                            $califFinal = $mi;
                        }

                        $firmado    = $registroFinal && !is_null($registroFinal->fecha_firma);
                        $modo_firma = $firmado ? ($registroFinal->lote_id || $registroFinal->firma_sig ? 'lote' : 'individual') : null;
                        $acusePdf   = $registroFinal->acuse_pdf ?? null;

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

        $materiasDisponibles = collect($documentos)->pluck('materia')->unique()->sort()->values();
        $unidadesDisponibles = collect($documentos)->pluck('unidad')->unique()->sort()->values();

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
            'quarter_name'         => $quarter,
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
