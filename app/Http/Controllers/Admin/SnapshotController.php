<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SnapshotController extends Controller
{
    public function store(Request $request)
    {
        $cuatrimestreId = $request->input('cuatrimestre_id');
        $quarterName    = $request->input('quarter_name');

        try {
            $exitCode = Artisan::call('snapshot:cargahoraria', array_filter([
                '--cuatrimestre_id' => $cuatrimestreId ?: null,
                '--quarter_name'    => $quarterName ?: null,
            ], fn($v) => !is_null($v)));

            $output = Artisan::output();

            $total = DB::table('materias_docentes_snapshots')->count();

            if ($exitCode === 0) {
                return back()->with('success', trim($output) . " Total en snapshot: {$total}.");
            }

            return back()->with('error', 'El comando finalizó con errores. Revisa logs.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al tomar snapshot: '.$e->getMessage());
        }
    }
}
