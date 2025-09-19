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

    // 1) Áreas y categorías (sin cambios)
    $areasUsuario = collect(explode(',', (string)($usuario->area ?? '')))
        ->map(fn($a) => trim($a))
        ->filter()
        ->values()
        ->all();
    $categoriasValidas = ['Titular C', 'Titular B', 'Titular A', 'Asociado C'];

    $baseQuery = User::whereNotNull('teacher_id')
        ->when(!empty($areasUsuario), function ($q) use ($areasUsuario) {
            $q->where(function ($qq) use ($areasUsuario) {
                foreach ($areasUsuario as $area) {
                    $qq->orWhereRaw('FIND_IN_SET(?, area)', [$area]);
                }
            });
        });

    $profesores = (clone $baseQuery)->whereIn('categoria', $categoriasValidas)
        ->orderBy('nombres')->get();

    if ($profesores->isEmpty()) {
        $profesores = (clone $baseQuery)->orderBy('nombres')->get();
    }

    // 2) Cuatrimestres disponibles (para <select>) y cuatrimestre actual correcto
    $quartersDisponibles = Submodulo::whereHas('subsection', fn ($q) => $q->where('modulo_id', 5))
        ->whereNotNull('quarter_name')
        ->selectRaw('TRIM(quarter_name) AS quarter_name, MAX(fecha_apertura) AS fa')
        ->groupBy(DB::raw('TRIM(quarter_name)'))
        ->orderByDesc('fa')
        ->pluck('quarter_name');

    $cuatrimestreActual = trim((string)$request->input('quarter_name', ''));

    if ($cuatrimestreActual === '') {
        // Más reciente cuya fecha_apertura sea hoy o antes
        $cuatrimestreActual = Submodulo::whereHas('subsection', fn ($q) => $q->where('modulo_id', 5))
            ->whereNotNull('quarter_name')
            ->where('fecha_apertura', '<=', now())
            ->orderByDesc('fecha_apertura')
            ->value('quarter_name')
            ?? ($quartersDisponibles->first() ?: null);
    }

    // 3) Submódulos del módulo 5 + filtro por subsección + quarter_name
    $query = Submodulo::whereHas('subsection', fn ($q) => $q->where('modulo_id', 5))
        ->with('subsection')
        ->when($request->filled('subseccion_id'), fn($q) => $q->where('subsection_id', $request->subseccion_id))
        ->when(!empty($cuatrimestreActual), fn($q) => 
            $q->whereRaw('TRIM(quarter_name) = ?', [trim($cuatrimestreActual)])
        );

    $submodulos = $query->get();

    // Orden deseado (sin cambios)
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

    // 4) Snapshot (igual, pero coherente con el mismo quarter)
    $snapConn = 'cargahoraria';
    if (!\Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
        $snapConn = config('database.default', 'mysql');
    }

    $materiasPorDocente = [];
    $teacherIdsSnap = collect();

    if (\Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
        $rowsSnapshot = DB::connection($snapConn)
            ->table('materias_docentes_snapshots')
            ->when(!empty($cuatrimestreActual), fn($q) => 
                $q->whereRaw('TRIM(quarter_name) = ?', [trim($cuatrimestreActual)])
            )
            ->get([
                'teacher_id','materia','grupo','programa','unidades',
                'subject_id','group_id','program_id','quarter_name','captured_at','source'
            ]);

        $teacherIdsSnap = $rowsSnapshot->pluck('teacher_id')->unique()->values();
        if ($teacherIdsSnap->isNotEmpty()) {
            $profesores = $profesores->whereIn('teacher_id', $teacherIdsSnap->all())->values();
        } else {
            $profesores = collect(); // sin snapshot para ese quarter
        }

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
        $profesores = collect();
    }

    // 5) Solo archivos de esos profesores y esos submódulos
    $archivos = SubmoduloArchivo::whereIn('user_id', $profesores->pluck('id'))
        ->whereIn('submodulo_id', $submodulos->pluck('id'))
        ->get();

    $archivoMap = [];
    foreach ($archivos as $archivo) {
        $archivoMap[$archivo->user_id][$archivo->submodulo_id] = $archivo;
    }

    // 6) Calificaciones (igual)
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

    // 7) Debug
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

        // ---------- SNAPSHOT: detectar conexión usable ----------
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
        $quarter     = $request->input('quarter_name');
        $quarterTrim = $quarter ? trim($quarter) : null;

        if ($quarterTrim && !$quartersDisponibles->contains($quarterTrim)) {
            $quartersDisponibles = $quartersDisponibles->push($quarterTrim)->unique()->values();
        }
        if (!$quarterTrim) {
            $quarterTrim = $quartersDocs->first() ?? $quartersSnap->first();
        }

        // ---------- Profes con documentos del cuatrimestre ----------
        $profIdsConDocs = DB::table('documentos_subidos')
            ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(quarter_name) = ?', [trim($quarterTrim)]))
            ->distinct()
            ->pluck('user_id');

        // ---------- Fallback: profes desde snapshots (teacher_id -> users.id) ----------
        $userIdsFromSnap = collect();
        $teacherIdsSnap = collect();
        if (Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
            $teacherIdsSnap = DB::connection($snapConn)
                ->table('materias_docentes_snapshots as mds')
                ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(mds.quarter_name) = ?', [trim($quarterTrim)]))
                ->distinct()
                ->pluck('mds.teacher_id');
            if ($teacherIdsSnap->isNotEmpty()) {
                if (Schema::hasColumn('users', 'teacher_id')) {
                    $userIdsFromSnap = $userIdsFromSnap->merge(
                        User::whereIn('teacher_id', $teacherIdsSnap)->pluck('id')
                    );
                }
                if (Schema::hasColumn('users', 'docente_id')) {
                    $userIdsFromSnap = $userIdsFromSnap->merge(
                        User::whereIn('docente_id', $teacherIdsSnap)->pluck('id')
                    );
                }
                $userIdsFromSnap = $userIdsFromSnap->unique()->values();
            }
        }

        // ---------- Profesores: PRIORIDAD (docs ∪ snapshots) + TODOS los docentes del área ----------
        $idsPrioritarios = $profIdsConDocs->merge($userIdsFromSnap)->unique()->values();

        $baseQueryProfes = User::query();

        if ($areas->isNotEmpty()) {
            $baseQueryProfes->where(function ($qq) use ($areas) {
                foreach ($areas as $area) {
                    $qq->orWhereRaw('FIND_IN_SET(?, area)', [$area]);
                }
            });
        }

        // Incluir SIEMPRE a docentes aunque no tengan actividad
        $baseQueryProfes->where(function ($qq) use ($idsPrioritarios) {
            if ($idsPrioritarios->isNotEmpty()) {
                $qq->orWhereIn('id', $idsPrioritarios);
            }
            if (Schema::hasColumn('users', 'teacher_id')) $qq->orWhereNotNull('teacher_id');
            if (Schema::hasColumn('users', 'docente_id')) $qq->orWhereNotNull('docente_id');
            if (Schema::hasColumn('users', 'tipo'))       $qq->orWhere('tipo', 'DOCENTE');
        });

        if ($idsPrioritarios->isNotEmpty()) {
            $idsList = $idsPrioritarios->implode(',');
            $baseQueryProfes->orderByRaw("FIELD(id, $idsList) DESC");
        }

        $profesores = $baseQueryProfes->orderBy('nombres')->get();

        // ---------- Parámetros de filtro ----------
        $profesorId           = $request->profesor_id;
        $materiaFiltro        = $request->materia;
        $unidadFiltro         = $request->unidad;
        $grupoFiltro          = $request->grupo;
        $profesorSeleccionado = $profesorId ? User::find($profesorId) : null;

        // ---------- Resolver teacher_id real del profesor seleccionado (si hay) ----------
        $teacherIdSeleccionado = null;
        if ($profesorSeleccionado) {
            if (Schema::hasColumn('users', 'teacher_id') && !empty($profesorSeleccionado->teacher_id)) {
                $teacherIdSeleccionado = (int)$profesorSeleccionado->teacher_id;
            } elseif (Schema::hasColumn('users', 'docente_id') && !empty($profesorSeleccionado->docente_id)) {
                $teacherIdSeleccionado = (int)$profesorSeleccionado->docente_id;
            }
        }

        // ---------- Resolver cuatrimestre_id en cargahoraria por nombre (sin COLLATE) ----------
        $cuatrimestreId = null;
        if ($quarterTrim && config('database.connections.cargahoraria.database')) {
            if (Schema::connection('cargahoraria')->hasTable('cuatrimestres')) {
                $cuatrimestreId = DB::connection('cargahoraria')
                    ->table('cuatrimestres')
                    ->whereRaw('TRIM(nombre) = ?', [trim($quarterTrim)])
                    ->value('id');
            }
        }

        // ---------- Asignaciones del cuatrimestre (base para combos y "pendientes") ----------

        // 1) Intentar desde SNAPSHOT si existe la tabla
        $asignaciones = collect();
        if (Schema::connection($snapConn)->hasTable('materias_docentes_snapshots')) {
            $snapQuery = DB::connection($snapConn)
                ->table('materias_docentes_snapshots as mds')
                ->select([
                    'mds.teacher_id',
                    'mds.materia',
                    'mds.grupo',
                    DB::raw('COALESCE(mds.unidades, 3) as unidades'),
                ])
                ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(mds.quarter_name) = ?', [trim($quarterTrim)]));

            if ($teacherIdSeleccionado) {
                $snapQuery->where('mds.teacher_id', $teacherIdSeleccionado);
            }

            $asignaciones = $snapQuery->get();
        }

        // 2) Fallback REAL a cargahoraria por cuatrimestre_id (sin comparar nombres de BD)
        if ($asignaciones->isEmpty() && config('database.connections.cargahoraria.database')) {
            if (
                Schema::connection('cargahoraria')->hasTable('teacher_subjects') &&
                Schema::connection('cargahoraria')->hasTable('subjects') &&
                Schema::connection('cargahoraria')->hasTable('groups') &&
                Schema::connection('cargahoraria')->hasTable('cuatrimestres')
            ) {
                $q = DB::connection('cargahoraria')
                    ->table('teacher_subjects as ts')
                    ->join('subjects as s', 's.subject_id', '=', 'ts.subject_id')
                    ->join('groups as g', 'g.group_id', '=', 'ts.group_id')
                    ->join('cuatrimestres as c', 'c.id', '=', 'g.cuatrimestre_id')
                    ->select([
                        'ts.teacher_id',
                        DB::raw('s.subject_name as materia'),
                        DB::raw('g.group_name   as grupo'),
                        DB::raw('COALESCE(s.unidades, 3) as unidades')
                    ]);

                if (!is_null($cuatrimestreId)) {
                    $q->where('g.cuatrimestre_id', $cuatrimestreId);
                } elseif ($quarterTrim) {
                    // Último recurso: por nombre, con TRIM (sin COLLATE)
                    $q->whereRaw('TRIM(c.nombre) = ?', [trim($quarterTrim)]);
                }

                if ($teacherIdSeleccionado) {
                    $q->where('ts.teacher_id', $teacherIdSeleccionado);
                }

                $asignaciones = $q->get();
            }
        }

        // ---------- Mapear teacher_id -> users.id ----------
        $teacherIdToUserId = collect();
        if ($asignaciones->isNotEmpty()) {
            $teacherIds = $asignaciones->pluck('teacher_id')->unique()->values();
            if (Schema::hasColumn('users', 'teacher_id')) {
                $teacherIdToUserId = $teacherIdToUserId->merge(
                    User::whereIn('teacher_id', $teacherIds)->pluck('id', 'teacher_id')
                );
            }
            if (Schema::hasColumn('users', 'docente_id')) {
                $teacherIdToUserId = $teacherIdToUserId->merge(
                    User::whereIn('docente_id', $teacherIds)->pluck('id', 'docente_id')
                );
            }
        }

        // Filtrar asignaciones por profesor seleccionado (si corresponde)
        $asignaciones = $asignaciones->map(function ($a) use ($teacherIdToUserId, $profesorSeleccionado) {
            $a->user_id = $teacherIdToUserId[$a->teacher_id] ?? ($profesorSeleccionado->id ?? null);
            return $a;
        })->filter(function ($a) use ($profesorSeleccionado) {
            return $profesorSeleccionado ? ($a->user_id === ($profesorSeleccionado->id ?? null)) : true;
        })->values();

        // ---------- Tipos de documento "esperados" ----------
        $tiposDoc = collect();
        if (Schema::hasTable('documentos_requeridos')) {
            $tiposDoc = DB::table('documentos_requeridos')
                ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(quarter_name) = ?', [trim($quarterTrim)]))
                ->pluck('tipo_documento')
                ->unique()
                ->values();
        }
        if ($tiposDoc->isEmpty()) {
            $tiposDoc = DB::table('documentos_subidos')
                ->distinct()
                ->orderBy('tipo_documento')
                ->pluck('tipo_documento');
        }
        if ($tiposDoc->isEmpty()) {
            $tiposDoc = collect(['Documento']);
        }

        // ---------- Documentos SUBIDOS (los reales) ----------
        $docsQuery = DB::table('documentos_subidos as ds')
            ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(ds.quarter_name) = ?', [trim($quarterTrim)]))
            ->when($profesorSeleccionado, fn($q) => $q->where('ds.user_id', $profesorSeleccionado->id))
            ->when($materiaFiltro, fn($q) => $q->where('ds.materia', $materiaFiltro))
            ->when($grupoFiltro, fn($q) => $q->where('ds.grupo', $grupoFiltro))
            ->when($unidadFiltro, fn($q) => $q->where('ds.unidad', (int)$unidadFiltro));

        $rowsSubidos = $docsQuery->get([
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

        // Index para localizar si un esperado ya está subido
        $idxSubidos = [];
        foreach ($rowsSubidos as $r) {
            $k = implode('|', [
                $r->user_id,
                trim((string)$r->materia),
                trim((string)$r->grupo),
                (int)$r->unidad,
                trim((string)$r->tipo_documento),
            ]);
            $idxSubidos[$k] = $r;
        }

        // ---------- Construir DOCUMENTOS ESPERADOS (pendientes + subidos) ----------
        $documentos = [];

        if ($profesorSeleccionado) {
            $asigsBase = $asignaciones;
            if ($asigsBase->isEmpty() && $rowsSubidos->isNotEmpty()) {
                $asigsBase = $rowsSubidos->map(fn($r) => (object)[
                    'user_id'  => $r->user_id,
                    'materia'  => $r->materia,
                    'grupo'    => $r->grupo,
                    'unidades' => $r->unidad ?? 1,
                ])->unique(fn($o) => $o->materia.'|'.$o->grupo);
            }

            foreach ($asigsBase as $a) {
                if (($a->user_id ?? null) !== $profesorSeleccionado->id) continue;

                $totalUnidades = max(1, (int)($a->unidades ?? 1));
                for ($u = 1; $u <= $totalUnidades; $u++) {
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

        // ---------- Combos (derivados de SUBIDOS ∪ ASIGNACIONES) ----------
        $materiasDesdeAsig = $asignaciones->pluck('materia');
        $gruposDesdeAsig   = $asignaciones->pluck('grupo');

        $materiasDesdeDocs = DB::table('documentos_subidos')
            ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(quarter_name) = ?', [trim($quarterTrim)]))
            ->when($profesorSeleccionado, fn($q) => $q->where('user_id', $profesorSeleccionado->id))
            ->pluck('materia');

        $gruposDesdeDocs = DB::table('documentos_subidos')
            ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(quarter_name) = ?', [trim($quarterTrim)]))
            ->when($profesorSeleccionado, fn($q) => $q->where('user_id', $profesorSeleccionado->id))
            ->pluck('grupo');

        $unidadesDesdeDocs = DB::table('documentos_subidos')
            ->when($quarterTrim, fn($q) => $q->whereRaw('TRIM(quarter_name) = ?', [trim($quarterTrim)]))
            ->when($profesorSeleccionado, fn($q) => $q->where('user_id', $profesorSeleccionado->id))
            ->pluck('unidad');

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
