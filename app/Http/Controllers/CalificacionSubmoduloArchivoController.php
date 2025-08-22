<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CalificacionSubmoduloArchivo;
use App\Models\SubmoduloArchivo;

class CalificacionSubmoduloArchivoController extends Controller
{
    public function store(Request $request)
    {
        // Validaciones claras
        $request->validate([
            'submodulo_archivo_id' => 'required|integer|exists:submodulo_archivos,id',
            'calificacion'         => 'required|integer|between:0,10',
        ]);

        $submoduloArchivoId = (int) $request->input('submodulo_archivo_id');
        $calificacion       = (int) $request->input('calificacion');

        $sa = SubmoduloArchivo::findOrFail($submoduloArchivoId);
        $profesorId = $sa->user_id;

        CalificacionSubmoduloArchivo::updateOrCreate(
            [
                'submodulo_archivo_id' => $submoduloArchivoId,
                'evaluador_id'         => Auth::id(),
            ],
            [
                'calificacion' => $calificacion,
                'profesor_id'  => $profesorId,
            ]
        );

        return back()->with('success', 'Calificación guardada correctamente.');
    }
}
