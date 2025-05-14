<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DocumentoSubido;

class DocumentoSubidoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'materia' => 'required|string',
            'unidad' => 'required|integer',
            'tipo_documento' => 'required|string',
            'archivo' => 'required|file|max:5120'
        ]);

        $archivoPath = $request->file('archivo')->store('documentos_subidos', 'public');

        DocumentoSubido::updateOrCreate([
            'user_id' => Auth::id(),
            'materia' => $request->materia,
            'unidad' => $request->unidad,
            'tipo_documento' => $request->tipo_documento,
        ], [
            'archivo' => $archivoPath,
        ]);

        return back()->with('success', 'Documento subido correctamente.');
    }
}
