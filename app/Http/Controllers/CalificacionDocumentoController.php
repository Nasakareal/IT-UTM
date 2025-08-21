<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CalificacionDocumento;
use App\Models\DocumentoSubido;

class CalificacionDocumentoController extends Controller
{
    // ====== LISTADO / RESUMEN POR PROFESOR ======
public function index(Request $request)
{
    $usuario = Auth::user();

    // Solo para SUBMÓDULOS (módulo 5)
    $categoriasValidasSub = ['Titular C', 'Titular B', 'Titular A', 'Asociado C'];

    /* =====================================================================
     * 1) BASE: TODOS los teacher_id que tienen carga (sin filtrar por áreas)
     * ===================================================================== */
    $teacherIdsConCarga = DB::connection('cargahoraria')
        ->table('teacher_subjects as ts')
        ->pluck('ts.teacher_id')
        ->unique();

    // Profesores (usuarios) que aparecen en carga (TODOS para GESTIÓN)
    $profesores = User::whereNotNull('teacher_id')
        ->whereIn('teacher_id', $teacherIdsConCarga)
        ->orderBy('nombres')
        ->get(['id','nombres','teacher_id','categoria']);

    $teacherIdsBase = $profesores->pluck('teacher_id')->unique();
    $userIdsBase    = $profesores->pluck('id')->unique()->all();

    // Subconjunto SOLO para SUBMÓDULOS (categorías válidas)
    $teacherIdsParaSub = $profesores
        ->whereIn('categoria', $categoriasValidasSub)
        ->pluck('teacher_id')
        ->unique();

    /* ============================================
     * 2) ENTREGADOS (Gestión y Submódulos)
     * ============================================ */
    // Gestión Académica
    $entregadosDocs = DB::table('documentos_subidos as ds')
        ->select('ds.user_id', DB::raw('COUNT(ds.id) as entregados'))
        ->groupBy('ds.user_id')
        ->pluck('entregados','user_id');

    // Submódulos (solo módulo 5)
    $entregadosSubs = DB::table('submodulo_archivos as sa')
        ->join('submodulos as sm', 'sm.id', '=', 'sa.submodulo_id')
        ->join('subsections as ss', 'ss.id', '=', 'sm.subsection_id')
        ->where('ss.modulo_id', 5)
        ->select('sa.user_id', DB::raw('COUNT(sa.id) as entregados'))
        ->groupBy('sa.user_id')
        ->pluck('entregados','user_id');

    /* ============================================
     * 3) PROMEDIOS (Gestión y Submódulos)
     * ============================================ */
    // Gestión Académica
    $avgPorDocumento = DB::table('calificacion_documentos as cd')
        ->select('cd.documento_id', DB::raw('AVG(cd.calificacion) as prom_item'))
        ->groupBy('cd.documento_id');

    $sumaDocs = DB::table('documentos_subidos as ds')
        ->joinSub($avgPorDocumento, 'pdoc', 'pdoc.documento_id', '=', 'ds.id')
        ->select(
            'ds.user_id',
            DB::raw('SUM(pdoc.prom_item) as suma_promedios'),
            DB::raw('COUNT(*) as items_calificados')
        )
        ->groupBy('ds.user_id')
        ->get()
        ->keyBy('user_id');

    // Submódulos (solo módulo 5)
    $avgPorSub = DB::table('calificacion_submodulo_archivos as csa')
        ->select('csa.submodulo_archivo_id', DB::raw('AVG(csa.calificacion) as prom_item'))
        ->groupBy('csa.submodulo_archivo_id');

    $sumaSubs = DB::table('submodulo_archivos as sa')
        ->join('submodulos as sm', 'sm.id', '=', 'sa.submodulo_id')
        ->join('subsections as ss', 'ss.id', '=', 'sm.subsection_id')
        ->joinSub($avgPorSub, 'psub', 'psub.submodulo_archivo_id', '=', 'sa.id')
        ->where('ss.modulo_id', 5)
        ->select(
            'sa.user_id',
            DB::raw('SUM(psub.prom_item) as suma_promedios'),
            DB::raw('COUNT(*) as items_calificados')
        )
        ->groupBy('sa.user_id')
        ->get()
        ->keyBy('user_id');

    /* ==============================================================
     * 4) ESPERADOS
     *    - Gestión: TODOS los profes con carga → por materia: 4*unidades + 3
     *    - Submódulos: SOLO categorías válidas → N submódulos del módulo 5
     * ============================================================== */
    // Gestión (por materia)
    $esperadosGestion = [];
    $rowsGestion = DB::connection('cargahoraria')
        ->table('teacher_subjects as ts')
        ->join('subjects as s', 'ts.subject_id', '=', 's.subject_id')
        ->whereIn('ts.teacher_id', $teacherIdsBase)
        ->distinct()
        ->get(['ts.teacher_id','ts.subject_id','ts.group_id','s.unidades']);

    foreach ($rowsGestion as $r) {
        $espG = (4 * (int)$r->unidades) + 3; // 2 especiales U1 + 1 final
        $esperadosGestion[$r->teacher_id] = ($esperadosGestion[$r->teacher_id] ?? 0) + $espG;
    }

    // Submódulos (mismo número de submódulos para cada prof válido)
    $numSubmodulosM5 = DB::table('submodulos as sm')
        ->join('subsections as ss', 'ss.id', '=', 'sm.subsection_id')
        ->where('ss.modulo_id', 5)
        ->distinct()
        ->count('sm.id');

    $esperadosSub = [];
    foreach ($teacherIdsParaSub as $tid) {
        $esperadosSub[$tid] = $numSubmodulosM5;
    }

    /* ============================================
     * 5) Salida unificada
     * ============================================ */
    $resumen = [];
    foreach ($profesores as $p) {
        $uid = (int)$p->id;
        $tid = $p->teacher_id;

        $espG = (int)($esperadosGestion[$tid] ?? 0);
        $espS = (int)($esperadosSub[$tid] ?? 0);
        $esperados = $espG + $espS;

        $entDocs = (int)($entregadosDocs[$uid] ?? 0);
        $entSubs = (int)($entregadosSubs[$uid] ?? 0);
        $entregados = $entDocs + $entSubs;

        $sDocsSum   = (float)(optional($sumaDocs->get($uid))->suma_promedios ?? 0);
        $sDocsCount = (int)(optional($sumaDocs->get($uid))->items_calificados ?? 0);

        $sSubsSum   = (float)(optional($sumaSubs->get($uid))->suma_promedios ?? 0);
        $sSubsCount = (int)(optional($sumaSubs->get($uid))->items_calificados ?? 0);

        $sumaTotal   = $sDocsSum + $sSubsSum;
        $calificados = $sDocsCount + $sSubsCount;

        $promedioGeneral = $esperados > 0 ? round($sumaTotal / $esperados, 2) : null;
        $cumplimiento    = $esperados > 0 ? round(($entregados / $esperados) * 100, 1) : null;

        $resumen[] = [
            'user_id'      => $uid,
            'nombre'       => trim($p->nombres ?? ''),
            'teacher_id'   => $tid,
            'esperados'    => $esperados,     // Gestión (todos) + Submódulos (solo 4 categorías)
            'entregados'   => $entregados,    // Gestión + Submódulos
            'calificados'  => $calificados,   // Gestión + Submódulos
            'promedio'     => $promedioGeneral,
            'cumplimiento' => $cumplimiento,
            'categoria'    => $p->categoria,
        ];
    }

    usort($resumen, fn($a,$b) => strcmp($a['nombre'], $b['nombre']));
    return view('settings.calificaciones.index', compact('resumen'));
}

    // ====== GUARDAR / ACTUALIZAR CALIFICACIÓN (desde la vista de gestión) ======
    public function store(Request $request)
    {
        $request->validate([
            'documento_id' => 'required|exists:documentos_subidos,id',
            'calificacion' => 'required|integer|between:0,10',
        ]);

        $documentoId = $request->input('documento_id');
        $evaluadorId = Auth::id();

        CalificacionDocumento::updateOrCreate(
            ['documento_id' => $documentoId, 'evaluador_id' => $evaluadorId],
            ['calificacion' => (int)$request->input('calificacion')]
        );

        return back()->with('success', 'Calificación guardada correctamente.');
    }

    // ====== (Opcional) Ver todas las calificaciones de un documento ======
    public function show($documento_id)
    {
        $calificaciones = CalificacionDocumento::where('documento_id', $documento_id)->get();
        return view('calificaciones.show', compact('calificaciones'));
    }
}
