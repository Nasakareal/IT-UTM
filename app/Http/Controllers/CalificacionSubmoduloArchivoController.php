<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CalificacionSubmoduloArchivo;

class CalificacionSubmoduloArchivoController extends Controller
{
    public function store(Request $request)
    {
        // VALIDACIÓN DURA: si no viene, regrésate con mensaje claro
        if (!$request->filled('submodulo_archivo_id')) {
            return back()->withErrors(['submodulo_archivo_id' => 'Falta submodulo_archivo_id'])->withInput();
        }
        if (!$request->filled('calificacion')) {
            return back()->withErrors(['calificacion' => 'Selecciona una calificación'])->withInput();
        }

        $request->validate([
            'submodulo_archivo_id' => 'required|integer|exists:submodulo_archivos,id',
            'calificacion'         => 'required|integer|between:0,10',
        ]);

        $submoduloArchivoId = (int) $request->input('submodulo_archivo_id');
        $calificacion       = (int) $request->input('calificacion');

        CalificacionSubmoduloArchivo::updateOrCreate(
            [
                'submodulo_archivo_id' => $submoduloArchivoId,
                'evaluador_id'         => Auth::id(),
            ],
            [
                'calificacion'         => $calificacion,
            ]
        );

        return back()->with('success', 'Calificación guardada correctamente.');
    }
}
