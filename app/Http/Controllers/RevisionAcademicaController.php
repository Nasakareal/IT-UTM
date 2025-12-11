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

    $areasUsuario = collect(explode(',', (string)($usuario->area ?? '')))
        ->map(fn($a) => trim($a))
        ->filter()
        ->values();

    $quartersDisponibles = Submodulo::whereHas('subsection', fn ($q) => $q->where('modulo_id', 5))
        ->whereNotNull('quarter_name')
        ->selectRaw('TRIM(quarter_name) AS quarter_name, MAX(fecha_apertura) AS fa')
        ->groupBy(DB::raw('TRIM(quarter_name)'))
        ->orderByDesc('fa')
        ->pluck('quarter_name');

    $cuatrimestreActual = trim((string)$request->input('quarter_name', ''));
    if ($cuatrimestreActual === '') {
        $cuatrimestreActual = Submodulo::whereHas('subsection', fn ($q) => $q->where('modulo_id', 5))
            ->whereNotNull('quarter_name')
            ->where('fecha_apertura', '<=', now())
            ->orderByDesc('fecha_apertura')
            ->value('quarter_name')
            ?? ($quartersDisponibles->first() ?: null);
    }
    $quarterTrim = $cuatrimestreActual ?: null;

    $query = Submodulo::whereHas('subsection', fn ($q) => $q->where('modulo_id', 5))
        ->with('subsection')
        ->when($request->filled('subseccion_id'), fn($q) => $q->where('subsection_id', $request->subseccion_id))
        ->when(!empty($quarterTrim), fn($q) => $q->whereRaw('TRIM(quarter_name) = ?', [trim($quarterTrim)]));

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
        ->orderBy('nombre')->get();

    $snapConn = 'cargahoraria';
    if (!\Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
        $snapConn = config('database.default', 'mysql');
    }

    $progArea = collect();
    if (config('database.connections.cargahoraria.database')
        && \Schema::connection('cargahoraria')->hasTable('programs')) {
        $progArea = DB::connection('cargahoraria')->table('programs')->pluck('area', 'program_id');
    }

    $teacherIdsVisibles = collect();

    if (\Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
        $rows = DB::connection($snapConn)
            ->table('materias_docentes_snapshots as mds')
            ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(mds.quarter_name) = ?', [trim($quarterTrim)]))
            ->get(['mds.teacher_id','mds.program_id']);

        if ($rows->isNotEmpty()) {
            $teacherIdsVisibles = $rows->filter(function($r) use ($areasUsuario, $progArea){
                    if ($areasUsuario->isEmpty()) return true;
                    $area = $progArea[$r->program_id] ?? null;
                    return $area && $areasUsuario->contains($area);
                })
                ->pluck('teacher_id')
                ->unique()
                ->values();
        }
    }

    if ($teacherIdsVisibles->isEmpty() && config('database.connections.cargahoraria.database')) {
        if (
            \Schema::connection('cargahoraria')->hasTable('teacher_subjects') &&
            \Schema::connection('cargahoraria')->hasTable('subjects') &&
            \Schema::connection('cargahoraria')->hasTable('groups') &&
            \Schema::connection('cargahoraria')->hasTable('cuatrimestres') &&
            \Schema::connection('cargahoraria')->hasTable('programs')
        ) {
            $cuatrimestreId = null;
            if ($quarterTrim && \Schema::connection('cargahoraria')->hasTable('cuatrimestres')) {
                $cuatrimestreId = DB::connection('cargahoraria')
                    ->table('cuatrimestres')->whereRaw('TRIM(nombre) = ?', [trim($quarterTrim)])->value('id');
            }

            $q = DB::connection('cargahoraria')
                ->table('teacher_subjects as ts')
                ->join('subjects as s', 's.subject_id', '=', 'ts.subject_id')
                ->join('groups as g', 'g.group_id', '=', 'ts.group_id')
                ->join('cuatrimestres as c', 'c.id', '=', 'g.cuatrimestre_id')
                ->join('programs as p', 'p.program_id', '=', 's.program_id')
                ->select('ts.teacher_id', 'p.area');

            if (!is_null($cuatrimestreId)) {
                $q->where('g.cuatrimestre_id', $cuatrimestreId);
            } elseif ($quarterTrim) {
                $q->whereRaw('TRIM(c.nombre) = ?', [trim($quarterTrim)]);
            }

            if ($areasUsuario->isNotEmpty()) {
                $q->whereIn('p.area', $areasUsuario->all());
            }

            $teacherIdsVisibles = $q->pluck('ts.teacher_id')->unique()->values();
        }
    }

    // -------------------------
    // PROFESORES + CATEGORÍA
    // -------------------------
    $profesores = collect();
    if ($teacherIdsVisibles->isNotEmpty()) {
        $idsUsers = collect();
        if (\Schema::hasColumn('users', 'teacher_id')) {
            $idsUsers = $idsUsers->merge(
                User::whereIn('teacher_id', $teacherIdsVisibles)->pluck('id')
            );
        }
        if (\Schema::hasColumn('users', 'docente_id')) {
            $idsUsers = $idsUsers->merge(
                User::whereIn('docente_id', $teacherIdsVisibles)->pluck('id')
            );
        }
        $idsUsers = $idsUsers->unique()->values();

        // categorías permitidas
        $categoriasPermitidas = [
            'Titular C',
            'Titular A',
            'Titular B',
            'Asociado C',
        ];

        $profesores = User::whereIn('id', $idsUsers)
            ->when(
                \Schema::hasColumn('users', 'categoria'),
                fn($q) => $q->whereIn('categoria', $categoriasPermitidas)
            )
            ->orderBy('nombres')
            ->get();
    }

    $materiasPorDocente = [];
    $teacherIdsSnap = collect();

    if (\Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
        $rowsSnapshot = DB::connection($snapConn)
            ->table('materias_docentes_snapshots as mds')
            ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(mds.quarter_name) = ?', [trim($quarterTrim)]))
            ->get([
                'mds.teacher_id','mds.materia','mds.grupo','mds.programa','mds.unidades',
                'mds.subject_id','mds.group_id','mds.program_id','mds.quarter_name','mds.captured_at','mds.source'
            ]);

        $teacherIdsSnap = $rowsSnapshot->pluck('teacher_id')->unique()->values();

        foreach ($rowsSnapshot as $r) {
            $areaProg = $progArea[$r->program_id] ?? null;
            if ($areasUsuario->isNotEmpty() && $areaProg && !$areasUsuario->contains($areaProg)) {
                continue;
            }
            if ($teacherIdsVisibles->isNotEmpty() && !$teacherIdsVisibles->contains($r->teacher_id)) {
                continue;
            }

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
                'programa_area'=> $areaProg,
            ];
        }
    }

    $archivos = SubmoduloArchivo::whereIn('user_id', $profesores->pluck('id'))
        ->whereIn('submodulo_id', $submodulos->pluck('id'))
        ->get();

    $archivoMap = [];
    foreach ($archivos as $archivo) {
        $archivoMap[$archivo->user_id][$archivo->submodulo_id] = $archivo;
    }

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

    $debug = [
        'profesores_count'      => $profesores->count(),
        'submodulos_count'      => $submodulos->count(),
        'archivos_count'        => $archivos->count(),
        'teacherIdsSnap_count'  => $teacherIdsSnap->count(),
        'cuatrimestreActual'    => $cuatrimestreActual,
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
        'quartersDisponibles',
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
        if (!\Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
            $snapConn = config('database.default', 'mysql');
        }

        $progArea = collect();
        if (config('database.connections.cargahoraria.database')
            && \Schema::connection('cargahoraria')->hasTable('programs')) {
            $progArea = DB::connection('cargahoraria')
                ->table('programs')
                ->pluck('area', 'program_id');
        }

        $quartersSnap = collect();
        if (\Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
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

        $quarter     = $request->input('quarter_name');
        $quarterTrim = $quarter ? trim($quarter) : null;

        if ($quarterTrim && !$quartersDisponibles->contains($quarterTrim)) {
            $quartersDisponibles = $quartersDisponibles->push($quarterTrim)->unique()->values();
        }
        if (!$quarterTrim) {
            $quarterTrim = $quartersDocs->first() ?? $quartersSnap->first();
        }

        $teacherIdsVisibles = collect();

        if (\Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
            $rows = DB::connection($snapConn)
                ->table('materias_docentes_snapshots as mds')
                ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(mds.quarter_name) = ?', [trim($quarterTrim)]))
                ->get(['mds.teacher_id','mds.program_id']);

            if ($rows->isNotEmpty()) {
                $teacherIdsVisibles = $rows->filter(function($r) use ($areas, $progArea){
                        if ($areas->isEmpty()) return true;
                        $area = $progArea[$r->program_id] ?? null;
                        return $area && $areas->contains($area);
                    })
                    ->pluck('teacher_id')
                    ->unique()
                    ->values();
            }
        }

        if ($teacherIdsVisibles->isEmpty() && config('database.connections.cargahoraria.database')) {
            if (
                \Schema::connection('cargahoraria')->hasTable('teacher_subjects') &&
                \Schema::connection('cargahoraria')->hasTable('subjects') &&
                \Schema::connection('cargahoraria')->hasTable('groups') &&
                \Schema::connection('cargahoraria')->hasTable('cuatrimestres') &&
                \Schema::connection('cargahoraria')->hasTable('programs')
            ) {
                $cuatrimestreId = null;
                if ($quarterTrim && \Schema::connection('cargahoraria')->hasTable('cuatrimestres')) {
                    $cuatrimestreId = DB::connection('cargahoraria')
                        ->table('cuatrimestres')
                        ->whereRaw('TRIM(nombre) = ?', [trim($quarterTrim)])
                        ->value('id');
                }

                $q = DB::connection('cargahoraria')
                    ->table('teacher_subjects as ts')
                    ->join('subjects as s', 's.subject_id', '=', 'ts.subject_id')
                    ->join('groups as g', 'g.group_id', '=', 'ts.group_id')
                    ->join('cuatrimestres as c', 'c.id', '=', 'g.cuatrimestre_id')
                    ->join('programs as p', 'p.program_id', '=', 's.program_id')
                    ->select('ts.teacher_id', 'p.area');

                if (!is_null($cuatrimestreId)) {
                    $q->where('g.cuatrimestre_id', $cuatrimestreId);
                } elseif ($quarterTrim) {
                    $q->whereRaw('TRIM(c.nombre) = ?', [trim($quarterTrim)]);
                }

                if ($areas->isNotEmpty()) {
                    $q->whereIn('p.area', $areas->all());
                }

                $teacherIdsVisibles = $q->pluck('ts.teacher_id')->unique()->values();
            }
        }

        $userIdsPorArea = collect();
        if ($teacherIdsVisibles->isNotEmpty()) {
            if (\Schema::hasColumn('users', 'teacher_id')) {
                $userIdsPorArea = $userIdsPorArea->merge(
                    User::whereIn('teacher_id', $teacherIdsVisibles)->pluck('id')
                );
            }
            if (\Schema::hasColumn('users', 'docente_id')) {
                $userIdsPorArea = $userIdsPorArea->merge(
                    User::whereIn('docente_id', $teacherIdsVisibles)->pluck('id')
                );
            }
            $userIdsPorArea = $userIdsPorArea->unique()->values();
        }

        $baseQueryProfes = User::query()
            ->when($userIdsPorArea->isNotEmpty(), fn($q) => $q->whereIn('id', $userIdsPorArea));
        $profesores = $baseQueryProfes->orderBy('nombres')->get();

        $profesorId           = $request->profesor_id;
        $materiaFiltro        = $request->materia;
        $unidadFiltro         = $request->unidad;
        $grupoFiltro          = $request->grupo;
        $profesorSeleccionado = $profesorId ? User::find($profesorId) : null;

        $teacherIdSeleccionado = null;
        if ($profesorSeleccionado) {
            if (\Schema::hasColumn('users', 'teacher_id') && !empty($profesorSeleccionado->teacher_id)) {
                $teacherIdSeleccionado = (int)$profesorSeleccionado->teacher_id;
            } elseif (\Schema::hasColumn('users', 'docente_id') && !empty($profesorSeleccionado->docente_id)) {
                $teacherIdSeleccionado = (int)$profesorSeleccionado->docente_id;
            }
            if ($userIdsPorArea->isNotEmpty() && !$userIdsPorArea->contains($profesorSeleccionado->id)) {
                $profesorSeleccionado = null;
                $teacherIdSeleccionado = null;
            }
        }

        $cuatrimestreId = null;
        if ($quarterTrim && config('database.connections.cargahoraria.database')) {
            if (\Schema::connection('cargahoraria')->hasTable('cuatrimestres')) {
                $cuatrimestreId = DB::connection('cargahoraria')
                    ->table('cuatrimestres')
                    ->whereRaw('TRIM(nombre) = ?', [trim($quarterTrim)])
                    ->value('id');
            }
        }

        $asignaciones = collect();
        if (\Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
            $snapQuery = DB::connection($snapConn)
                ->table('materias_docentes_snapshots as mds')
                ->select([
                    'mds.teacher_id',
                    DB::raw('mds.materia as materia'),
                    DB::raw('mds.grupo   as grupo'),
                    DB::raw('COALESCE(mds.unidades, 3) as unidades'),
                    'mds.program_id',
                ])
                ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(mds.quarter_name) = ?', [trim($quarterTrim)]));

            if ($teacherIdSeleccionado) $snapQuery->where('mds.teacher_id', $teacherIdSeleccionado);

            $asignaciones = $snapQuery->get()->filter(function($a) use ($areas, $progArea){
                if ($areas->isEmpty()) return true;
                $area = $progArea[$a->program_id] ?? null;
                return $area && $areas->contains($area);
            })->values();
        }

        if ($asignaciones->isEmpty() && config('database.connections.cargahoraria.database')) {
            if (
                \Schema::connection('cargahoraria')->hasTable('teacher_subjects') &&
                \Schema::connection('cargahoraria')->hasTable('subjects') &&
                \Schema::connection('cargahoraria')->hasTable('groups') &&
                \Schema::connection('cargahoraria')->hasTable('cuatrimestres') &&
                \Schema::connection('cargahoraria')->hasTable('programs')
            ) {
                $q = DB::connection('cargahoraria')
                    ->table('teacher_subjects as ts')
                    ->join('subjects as s', 's.subject_id', '=', 'ts.subject_id')
                    ->join('groups as g', 'g.group_id', '=', 'ts.group_id')
                    ->join('cuatrimestres as c', 'c.id', '=', 'g.cuatrimestre_id')
                    ->join('programs as p', 'p.program_id', '=', 's.program_id')
                    ->select([
                        'ts.teacher_id',
                        DB::raw('s.subject_name as materia'),
                        DB::raw('g.group_name   as grupo'),
                        DB::raw('COALESCE(s.unidades, 3) as unidades'),
                        's.program_id',
                        DB::raw('p.area as programa_area'),
                    ]);

                if (!is_null($cuatrimestreId)) $q->where('g.cuatrimestre_id', $cuatrimestreId);
                elseif ($quarterTrim)         $q->whereRaw('TRIM(c.nombre) = ?', [trim($quarterTrim)]);

                if ($teacherIdSeleccionado) $q->where('ts.teacher_id', $teacherIdSeleccionado);
                if ($areas->isNotEmpty())   $q->whereIn('p.area', $areas->all());

                $asignaciones = $q->get();
            }
        }

        $teacherIdToUserId = collect();
        if ($asignaciones->isNotEmpty()) {
            $teacherIds = $asignaciones->pluck('teacher_id')->unique()->values();
            if (\Schema::hasColumn('users', 'teacher_id')) {
                $teacherIdToUserId = $teacherIdToUserId->merge(
                    User::whereIn('teacher_id', $teacherIds)->pluck('id', 'teacher_id')
                );
            }
            if (\Schema::hasColumn('users', 'docente_id')) {
                $teacherIdToUserId = $teacherIdToUserId->merge(
                    User::whereIn('docente_id', $teacherIds)->pluck('id', 'docente_id')
                );
            }
        }

        $asignaciones = $asignaciones->map(function ($a) use ($teacherIdToUserId, $profesorSeleccionado) {
            $a->user_id = $teacherIdToUserId[$a->teacher_id] ?? ($profesorSeleccionado->id ?? null);
            return $a;
        })->filter(function ($a) use ($profesorSeleccionado) {
            return $profesorSeleccionado ? ($a->user_id === ($profesorSeleccionado->id ?? null)) : true;
        })->values();

        $tiposDoc = collect();
        if (\Schema::hasTable('documentos_requeridos')) {
            $tiposDoc = DB::table('documentos_requeridos')
                ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(quarter_name) = ?', [trim($quarterTrim)]))
                ->pluck('tipo_documento')->unique()->values();
        }
        if ($tiposDoc->isEmpty()) {
            $tiposDoc = DB::table('documentos_subidos')->distinct()->orderBy('tipo_documento')->pluck('tipo_documento');
        }
        if ($tiposDoc->isEmpty()) $tiposDoc = collect(['Documento']);

        $docsQuery = DB::table('documentos_subidos as ds')
            ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(ds.quarter_name) = ?', [trim($quarterTrim)]))
            ->when($profesorSeleccionado, fn($q) => $q->where('ds.user_id', $profesorSeleccionado->id))
            ->when($materiaFiltro, fn($q) => $q->where('ds.materia', $materiaFiltro))
            ->when($grupoFiltro, fn($q) => $q->where('ds.grupo', $grupoFiltro))
            ->when($unidadFiltro, fn($q) => $q->where('ds.unidad', (int)$unidadFiltro));

        $rowsSubidos = $docsQuery->get([
            'ds.id','ds.user_id','ds.materia','ds.grupo','ds.unidad','ds.quarter_name',
            'ds.tipo_documento','ds.archivo','ds.acuse_pdf','ds.firma_sig','ds.lote_id',
            'ds.fecha_firma','ds.created_at',
        ]);

        $mapAreaMG = [];
        foreach ($asignaciones as $a) {
            $area = $a->programa_area ?? ($progArea[$a->program_id] ?? null);
            if ($area) {
                $mapAreaMG[trim((string)$a->materia).'|'.trim((string)$a->grupo)] = $area;
            }
        }

        if (!empty($mapAreaMG) && $areas->isNotEmpty()) {
            $rowsSubidos = $rowsSubidos->filter(function($r) use ($mapAreaMG, $areas){
                $key = trim((string)$r->materia).'|'.trim((string)$r->grupo);
                return isset($mapAreaMG[$key]) && $areas->contains($mapAreaMG[$key]);
            })->values();
        }

        $idxSubidos = [];
        foreach ($rowsSubidos as $r) {
            $k = implode('|', [
                $r->user_id, trim((string)$r->materia), trim((string)$r->grupo),
                (int)$r->unidad, trim((string)$r->tipo_documento),
            ]);
            $idxSubidos[$k] = $r;
        }

        $documentos = [];
        if ($profesorSeleccionado) {
            $asigsBase = $asignaciones;
            if ($asigsBase->isEmpty() && $rowsSubidos->isNotEmpty()) {
                $asigsBase = $rowsSubidos->map(fn($r) => (object)[
                    'user_id'       => $r->user_id,
                    'materia'       => $r->materia,
                    'grupo'         => $r->grupo,
                    'unidades'      => $r->unidad ?? 1,
                    'programa_area' => $mapAreaMG[trim((string)$r->materia).'|'.trim((string)$r->grupo)] ?? null,
                ])->unique(fn($o) => $o->materia.'|'.$o->grupo);
            }

            foreach ($asigsBase as $a) {
                $area = $a->programa_area ?? ($progArea[$a->program_id] ?? null);
                if ($areas->isNotEmpty() && $area && !$areas->contains($area)) continue;
                if (($a->user_id ?? null) !== $profesorSeleccionado->id) continue;

                $totalUnidades = max(1, (int)($a->unidades ?? 1));
                for ($u=1; $u <= $totalUnidades; $u++) {
                    foreach ($tiposDoc as $td) {
                        $k = implode('|', [
                            $profesorSeleccionado->id,
                            trim((string)$a->materia),
                            trim((string)$a->grupo),
                            $u,
                            trim((string)$td),
                        ]);
                        if (isset($idxSubidos[$k])) {
                            $r = $idxSubidos[$k];
                            $mi = CalificacionDocumento::where('documento_id', $r->id)
                                ->where('evaluador_id', auth()->id())->value('calificacion');
                            if (is_null($mi)) {
                                $mi = CalificacionDocumento::where('documento_id', $r->id)
                                    ->orderByDesc('id')->value('calificacion');
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
                        } else {
                            $documentos[] = [
                                'materia'         => (string)$a->materia,
                                'programa'        => null,
                                'grupo'           => (string)$a->grupo,
                                'unidad'          => (int)$u,
                                'tipo_documento'  => (string)$td,
                                'created_at'      => null,
                                'plantilla'       => null,
                                'entregado'       => false,
                                'archivo_subido'  => null,
                                'id'              => null,
                                'mi_calificacion' => null,
                                'es_actual'       => false,
                                'firmado'         => false,
                                'modo_firma'      => null,
                                'acuse_pdf'       => null,
                            ];
                        }
                    }
                }
            }

            usort($documentos, function ($a, $b) {
                return [$a['materia'], $a['grupo'], $a['unidad'], $a['tipo_documento']]
                    <=>  [$b['materia'], $b['grupo'], $b['unidad'], $b['tipo_documento']];
            });
        }

        $materiasDesdeAsig = $asignaciones->pluck('materia');
        $gruposDesdeAsig   = $asignaciones->pluck('grupo');

        $materiasDesdeDocs = $rowsSubidos->pluck('materia');
        $gruposDesdeDocs   = $rowsSubidos->pluck('grupo');
        $unidadesDesdeDocs = $rowsSubidos->pluck('unidad');

        $unidadesDesdeAsig = collect();
        if ($asignaciones->isNotEmpty()) {
            $maxU = $asignaciones->max('unidades') ?? 1;
            $unidadesDesdeAsig = collect(range(1, max(1, (int)$maxU)));
        }

        $materiasDisponibles = $materiasDesdeDocs->merge($materiasDesdeAsig)->filter()->unique()->sort()->values();
        $gruposDisponibles   = $gruposDesdeDocs->merge($gruposDesdeAsig)->filter()->unique()->sort()->values();
        $unidadesDisponibles = $unidadesDesdeDocs->merge($unidadesDesdeAsig)->filter()->unique()->sort()->values();

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
