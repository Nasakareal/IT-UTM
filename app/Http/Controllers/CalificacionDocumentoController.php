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

    // Categorías que SÍ deben entregar/ser evaluadas
    $categoriasValidas = ['Titular C', 'Titular B', 'Titular A', 'Asociado C'];

    // (Opcional) limitar por áreas del subdirector logueado
    $areas = collect(explode(',', (string)($usuario->area ?? '')))
        ->map(fn($a)=>trim($a))->filter()->values();

    // 1) Teacher IDs con carga en (tus) áreas
    $teacherIdsEnAreas = DB::connection('cargahoraria')
        ->table('teacher_subjects as ts')
        ->join('subjects as s', 'ts.subject_id', '=', 's.subject_id')
        ->join('programs as p', 's.program_id', '=', 'p.program_id')
        ->when($areas->isNotEmpty(), fn($q) => $q->whereIn('p.area', $areas))
        ->pluck('ts.teacher_id')
        ->unique();

    // 2) Profes que:
    //    - tienen teacher_id
    //    - están en categorías válidas
    //    - además aparecen en carga (áreas del subdirector)
    $profesores = User::whereNotNull('teacher_id')
        ->whereIn('categoria', $categoriasValidas)
        ->whereIn('teacher_id', $teacherIdsEnAreas)
        ->orderBy('nombres')
        ->get(['id','nombres','teacher_id','categoria']);

    // Mapea teacher_id válidos finales (por si hay depuración extra)
    $teacherIdsValidos = $profesores->pluck('teacher_id')->unique();

    // 3) ENTREGADOS (documentos_subidos - Gestión Académica)
    $entregadosDocs = DB::table('documentos_subidos as ds')
        ->select('ds.user_id', DB::raw('COUNT(ds.id) as entregados'))
        ->groupBy('ds.user_id')
        ->pluck('entregados','user_id');

    // 3bis) ENTREGADOS de Submódulos (solo módulo 5)
    $entregadosSubs = DB::table('submodulo_archivos as sa')
        ->join('submodulos as sm', 'sm.id', '=', 'sa.submodulo_id')
        ->join('subsections as ss', 'ss.id', '=', 'sm.subsection_id')
        ->where('ss.modulo_id', 5)
        ->select('sa.user_id', DB::raw('COUNT(sa.id) as entregados'))
        ->groupBy('sa.user_id')
        ->pluck('entregados','user_id');

    // 4) PROMEDIO por DOCUMENTO (promedio por ítem -> sumar por profe)
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

    // 5) PROMEDIO por SUBMÓDULO (solo módulo 5)
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

    // 6) ESPERADOS (solo para los teacher_id válidos y dentro de tus áreas)
    //    Fórmula: 4 * unidades + 2 (especiales U1) + 1 (final)
    $esperadosPorTeacher = [];
    $rowsEsperados = DB::connection('cargahoraria')
        ->table('teacher_subjects as ts')
        ->join('subjects as s', 'ts.subject_id', '=', 's.subject_id')
        ->join('programs as p', 's.program_id', '=', 'p.program_id')
        ->when($areas->isNotEmpty(), fn($q)=>$q->whereIn('p.area', $areas))
        ->whereIn('ts.teacher_id', $teacherIdsValidos) // <- clave: solo los de categoría válida
        ->distinct()
        ->get(['ts.teacher_id','ts.subject_id','ts.group_id','s.unidades']);

    foreach ($rowsEsperados as $r) {
        $esp = (4 * (int)$r->unidades) + 3;
        $esperadosPorTeacher[$r->teacher_id] = ($esperadosPorTeacher[$r->teacher_id] ?? 0) + $esp;
    }

    // 7) Salida unificada (UNA sola calificación que castiga faltantes)
    $resumen = [];
    foreach ($profesores as $p) {
        $uid       = (int)$p->id;
        $esperados = (int)($esperadosPorTeacher[$p->teacher_id] ?? 0);

        $entDocs   = (int)($entregadosDocs[$uid] ?? 0);
        $entSubs   = (int)($entregadosSubs[$uid] ?? 0);
        $entregados= $entDocs + $entSubs;

        $sDocsSum   = (float)(optional($sumaDocs->get($uid))->suma_promedios ?? 0);
        $sDocsCount = (int)(optional($sumaDocs->get($uid))->items_calificados ?? 0);

        $sSubsSum   = (float)(optional($sumaSubs->get($uid))->suma_promedios ?? 0);
        $sSubsCount = (int)(optional($sumaSubs->get($uid))->items_calificados ?? 0);

        $sumaTotal   = $sDocsSum + $sSubsSum;
        $calificados = $sDocsCount + $sSubsCount;

        // ÚNICO promedio: sobre ESPERADOS (faltantes = 0)
        $promedioGeneral = $esperados > 0 ? round($sumaTotal / $esperados, 2) : null;

        $cumplimiento = $esperados > 0 ? round(($entregados / $esperados) * 100, 1) : null;

        $resumen[] = [
            'user_id'      => $uid,
            'nombre'       => trim($p->nombres ?? ''),
            'teacher_id'   => $p->teacher_id,
            'esperados'    => $esperados,
            'entregados'   => $entregados,
            'calificados'  => $calificados,
            'promedio'     => $promedioGeneral,
            'cumplimiento' => $cumplimiento,
            'categoria'    => $p->categoria, // por si la quieres mostrar
        ];
    }

    // (Opcional) si quieres ocultar los que al final tienen 0 esperados
    // $resumen = array_values(array_filter($resumen, fn($r) => $r['esperados'] > 0));

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
