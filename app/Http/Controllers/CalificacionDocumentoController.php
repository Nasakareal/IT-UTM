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
        // 1) Profesores con teacher_id
        $profesores = User::whereNotNull('teacher_id')
            ->orderBy('nombres')
            ->get(['id','nombres','teacher_id']);

        // 2) Documentos ENTREGADOS por profesor
        $entregados = DB::table('documentos_subidos as ds')
            ->select('ds.user_id', DB::raw('COUNT(ds.id) as entregados'))
            ->groupBy('ds.user_id')
            ->pluck('entregados','user_id');

        // 3) Calificados y SUMA de calificaciones por profesor (promedio por documento → suma por profesor)
        //    - pd: promedio por documento (si hay varios evaluadores en el mismo doc, se promedian)
        $porDocumento = DB::table('calificacion_documentos as cd')
            ->select('cd.documento_id', DB::raw('AVG(cd.calificacion) as prom_doc'))
            ->groupBy('cd.documento_id');

        //    - sumas: suma de prom_doc por profesor + conteo de documentos calificados
        $sumas = DB::table('documentos_subidos as ds')
            ->joinSub($porDocumento, 'pd', 'pd.documento_id', '=', 'ds.id')
            ->select(
                'ds.user_id',
                DB::raw('SUM(pd.prom_doc) as suma_promedios'),
                DB::raw('COUNT(pd.documento_id) as calificados')
            )
            ->groupBy('ds.user_id')
            ->get()
            ->keyBy('user_id');

        // 4) ESPERADOS por profesor: sum(4*unidades + 3) por cada (subject_id, group_id)
        $esperadosPorTeacher = [];
        $rowsEsperados = DB::connection('cargahoraria')
            ->table('teacher_subjects as ts')
            ->join('subjects as s', 'ts.subject_id', '=', 's.subject_id')
            ->join('groups as g',   'ts.group_id',   '=', 'g.group_id')
            ->distinct()
            ->get(['ts.teacher_id','ts.subject_id','ts.group_id','s.unidades']);

        foreach ($rowsEsperados as $r) {
            $esp = (4 * (int)$r->unidades) + 3; // 4 por unidad + 2 especiales U1 + 1 final
            $esperadosPorTeacher[$r->teacher_id] = ($esperadosPorTeacher[$r->teacher_id] ?? 0) + $esp;
        }

        // 5) Armar salida por profesor
        $resumen = [];
        foreach ($profesores as $p) {
            $uid        = $p->id;
            $esperados  = (int) ($esperadosPorTeacher[$p->teacher_id] ?? 0);
            $ent        = (int) ($entregados[$uid] ?? 0);
            $califCount = (int) (optional($sumas->get($uid))->calificados ?? 0);
            $sumaDocs   = (float) (optional($sumas->get($uid))->suma_promedios ?? 0);

            // Promedio general = suma de calificaciones / esperados (faltantes cuentan como 0)
            $promedioGeneral = $esperados > 0 ? round($sumaDocs / $esperados, 2) : null;

            $resumen[] = [
                'user_id'    => $uid,
                'nombre'     => trim($p->nombres ?? ''),
                'teacher_id' => $p->teacher_id,
                'esperados'  => $esperados,
                'entregados' => $ent,
                'calificados'=> $califCount,
                'promedio'   => $promedioGeneral,
            ];
        }

        // Orden alfabético por nombre
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
