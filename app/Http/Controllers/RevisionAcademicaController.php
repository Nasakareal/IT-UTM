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

    $snapConn = 'cargahoraria';
    if (!Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
        $snapConn = config('database.default', 'mysql');
    }

    // ---------- Cuatrimestres disponibles (snapshots + documentos) ----------
    $quartersSnap = collect();
    if (Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
        $quartersSnap = DB::connection($snapConn)
            ->table('materias_docentes_snapshots as mds')
            ->select(DB::raw('TRIM(mds.quarter_name) as quarter_name'))
            ->groupBy(DB::raw('TRIM(mds.quarter_name)'))
            ->pluck('quarter_name');
    }

    $quartersDocs = DB::table('documentos_subidos')
        ->select(DB::raw('TRIM(quarter_name) as quarter_name'), DB::raw('MAX(created_at) as last_cap'))
        ->groupBy(DB::raw('TRIM(quarter_name)'))
        ->orderByDesc('last_cap')
        ->pluck('quarter_name');

    $quartersDisponibles = $quartersSnap->merge($quartersDocs)->filter()->unique()->values();

    // ---------- Cuatrimestre seleccionado ----------
    $quarter = $request->input('quarter_name');
    $quarterTrim = $quarter ? trim($quarter) : null;

    // Si el usuario pidió un cuatri que no existe en BD, lo agregamos para que aparezca en el <select>
    if ($quarterTrim && !$quartersDisponibles->contains($quarterTrim)) {
        $quartersDisponibles = $quartersDisponibles->push($quarterTrim)->unique()->values();
    }

    if (!$quarterTrim) {
        $quarterTrim = $quartersDocs->first() ?? $quartersSnap->first();
    }

    // ---------- Profes con documentos del cuatrimestre (principal) ----------
    $profIdsConDocs = DB::table('documentos_subidos')
        ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(quarter_name) = ?', [$quarterTrim]))
        ->distinct()
        ->pluck('user_id');

    // ---------- Fallback: sacar profes desde snapshots (teacher_id -> users.id) ----------
    $userIdsFromSnap = collect();
    if ($profIdsConDocs->isEmpty() && Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
        // 1) Tomamos teacher_id de snapshots
        $teacherIdsSnap = DB::connection($snapConn)
            ->table('materias_docentes_snapshots as mds')
            ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(mds.quarter_name) = ?', [$quarterTrim]))
            ->distinct()
            ->pluck('mds.teacher_id');

        if ($teacherIdsSnap->isNotEmpty()) {
            // 2) Intentamos mapear contra users.teacher_id
            if (Schema::hasColumn('users', 'teacher_id')) {
                $userIdsFromSnap = $userIdsFromSnap->merge(
                    User::whereIn('teacher_id', $teacherIdsSnap)->pluck('id')
                );
            }
            // 3) Intentamos mapear contra users.docente_id (por si tu app usa este)
            if (Schema::hasColumn('users', 'docente_id')) {
                $userIdsFromSnap = $userIdsFromSnap->merge(
                    User::whereIn('docente_id', $teacherIdsSnap)->pluck('id')
                );
            }

            $userIdsFromSnap = $userIdsFromSnap->unique()->values();
        }
    }

    // ---------- Elegir la fuente de profesores ----------
    $idsParaProfes = $profIdsConDocs->isNotEmpty() ? $profIdsConDocs : $userIdsFromSnap;

    $baseQueryProfes = User::query();

    if ($idsParaProfes->isNotEmpty()) {
        $baseQueryProfes->whereIn('id', $idsParaProfes);
    }

    // Filtro por áreas (si aplica)
    if ($areas->isNotEmpty()) {
        $baseQueryProfes->where(function ($qq) use ($areas) {
            foreach ($areas as $area) {
                $qq->orWhereRaw('FIND_IN_SET(?, area)', [$area]);
            }
        });
    }

    $profesores = $baseQueryProfes->orderBy('nombres')->get();

    $profesorId        = $request->profesor_id;
    $materiaFiltro     = $request->materia;
    $unidadFiltro      = $request->unidad;
    $grupoFiltro       = $request->grupo;
    $profesorSeleccionado = $profesorId ? User::find($profesorId) : null;

    // ---------- Documentos del cuatrimestre ----------
    $docsQuery = DB::table('documentos_subidos as ds')
        ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(ds.quarter_name) = ?', [$quarterTrim]))
        ->when($profesorSeleccionado, fn($q) => $q->where('ds.user_id', $profesorSeleccionado->id))
        ->when($materiaFiltro, fn($q) => $q->where('ds.materia', $materiaFiltro))
        ->when($grupoFiltro, fn($q) => $q->where('ds.grupo', $grupoFiltro))
        ->when($unidadFiltro, fn($q) => $q->where('ds.unidad', (int)$unidadFiltro))
        ->orderBy('ds.materia')
        ->orderBy('ds.grupo')
        ->orderBy('ds.unidad');

    $rows = $docsQuery->get([
        'ds.id',
        'ds.user_id',
        'ds.materia',
        'ds.grupo',
        'ds.unidad',
        'ds.quarter_name',
        'ds.tipo_documento',
        'ds.archivo',
        'ds.acuse_pdf',
        'ds.firma_sig',
        'ds.lote_id',
        'ds.fecha_firma',
        'ds.created_at',
    ]);

    $documentos = [];
    foreach ($rows as $r) {
        $mi = CalificacionDocumento::where('documento_id', $r->id)
            ->where('evaluador_id', auth()->id())
            ->value('calificacion');

        if (is_null($mi)) {
            $mi = CalificacionDocumento::where('documento_id', $r->id)
                ->orderByDesc('id')
                ->value('calificacion');
        }

        $firmado    = !is_null($r->fecha_firma);
        $modo_firma = $firmado ? ($r->lote_id || $r->firma_sig ? 'lote' : 'individual') : null;

        $documentos[] = [
            'materia'         => $r->materia,
            'programa'        => null,
            'grupo'           => $r->grupo,
            'unidad'          => (int)$r->unidad,
            'tipo_documento'  => $r->tipo_documento,
            'created_at'      => $r->created_at,
            'plantilla'       => null,
            'entregado'       => true,
            'archivo_subido'  => $r->archivo,
            'id'              => $r->id,
            'mi_calificacion' => $mi,
            'es_actual'       => false,
            'firmado'         => $firmado,
            'modo_firma'      => $modo_firma,
            'acuse_pdf'       => $r->acuse_pdf,
        ];
    }

    // ---------- Combos (derivados de documentos) ----------
    $materiasDisponibles = DB::table('documentos_subidos')
        ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(quarter_name) = ?', [$quarterTrim]))
        ->when($profesorSeleccionado, fn($q) => $q->where('user_id', $profesorSeleccionado->id))
        ->distinct()->orderBy('materia')->pluck('materia');

    $unidadesDisponibles = DB::table('documentos_subidos')
        ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(quarter_name) = ?', [$quarterTrim]))
        ->when($profesorSeleccionado, fn($q) => $q->where('user_id', $profesorSeleccionado->id))
        ->distinct()->orderBy('unidad')->pluck('unidad');

    $gruposDisponibles = DB::table('documentos_subidos')
        ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(quarter_name) = ?', [$quarterTrim]))
        ->when($profesorSeleccionado, fn($q) => $q->where('user_id', $profesorSeleccionado->id))
        ->distinct()->orderBy('grupo')->pluck('grupo');

    return view('revision_academica.solo_gestion', [
        'profesores'           => $profesores,
        'profesorSeleccionado' => $profesorSeleccionado,
        'materiasDisponibles'  => $materiasDisponibles,
        'unidadesDisponibles'  => $unidadesDisponibles,
        'documentos'           => $documentos,
        'gruposDisponibles'    => $gruposDisponibles,
        'quarter_name'         => $quarterTrim,
        'quartersDisponibles'  => $quartersDisponibles,
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
