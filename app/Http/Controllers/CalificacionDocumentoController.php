<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CalificacionDocumento;
use App\Models\DocumentoSubido;

class CalificacionDocumentoController extends Controller
{
    // Guarda o actualiza la calificación
    public function store(Request $request)
    {
        $request->validate([
            'documento_id' => 'required|exists:documentos_subidos,id',
            'calificacion' => 'required|integer|between:1,10',
        ]);

        $documentoId = $request->input('documento_id');
        $evaluadorId = Auth::id();

        $calificacion = CalificacionDocumento::updateOrCreate(
            [
                'documento_id' => $documentoId,
                'evaluador_id' => $evaluadorId,
            ],
            [
                'calificacion' => $request->input('calificacion'),
            ]
        );

        return back()->with('success', 'Calificación guardada correctamente.');
    }

    // Mostrar calificaciones (opcional si usas una vista tipo tabla)
    public function show($documento_id)
    {
        $calificaciones = CalificacionDocumento::where('documento_id', $documento_id)->get();

        return view('calificaciones.show', compact('calificaciones'));
    }
}
