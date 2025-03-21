<?php

namespace App\Http\Controllers;

use App\Models\Correspondencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CorrespondenciaController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $correspondencias = $user->hasRole('Administrador') 
            ? Correspondencia::all() 
            : Correspondencia::where('usuario_id', $user->id)->get();

        return view('correspondencias.index', compact('correspondencias'));
    }

    public function create()
    {
        return view('correspondencias.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'remitente' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:255',
            'oficio' => 'nullable|string|max:255',
            'tipo_documento' => 'required|in:Oficio,Nota Informativa,Otro',
            'fecha_elaboracion' => 'required|date',
            'tema' => 'required|string|max:250',
            'descripcion_asunto' => 'required|string|max:1500',
            'archivo_pdf' => 'nullable|file|mimes:pdf|max:2048',
            'observaciones' => 'nullable|string',
        ]);

        $archivoPath = $request->file('archivo_pdf') 
            ? $request->file('archivo_pdf')->store('correspondencias') 
            : null;

        Correspondencia::create([
            'remitente' => $request->remitente,
            'referencia' => $request->referencia,
            'oficio' => $request->oficio,
            'tipo_documento' => $request->tipo_documento,
            'fecha_elaboracion' => $request->fecha_elaboracion,
            'tema' => $request->tema,
            'descripcion_asunto' => $request->descripcion_asunto,
            'archivo_pdf' => $archivoPath,
            'observaciones' => $request->observaciones,
            'estado' => 'En proceso',
            'usuario_id' => Auth::id(),
        ]);

        return redirect()->route('correspondencias.index')->with('success', 'Correspondencia enviada correctamente.');
    }

    public function show(Correspondencia $correspondencia)
    {
        $this->authorize('view', $correspondencia);

        return view('correspondencias.show', compact('correspondencia'));
    }

    public function edit(Correspondencia $correspondencia)
    {
        $this->authorize('update', $correspondencia);

        return view('correspondencias.edit', compact('correspondencia'));
    }

    public function update(Request $request, Correspondencia $correspondencia)
    {
        $this->authorize('update', $correspondencia);

        $request->validate([
            'estado' => 'required|in:En proceso,Pendiente,Concluido',
        ]);

        $correspondencia->update(['estado' => $request->estado]);

        return redirect()->route('correspondencias.index')->with('success', 'Estado actualizado correctamente.');
    }

    public function destroy(Correspondencia $correspondencia)
    {
        $this->authorize('update', $correspondencia);

        if ($correspondencia->archivo_pdf) {
            Storage::delete($correspondencia->archivo_pdf);
        }

        $correspondencia->delete();

        return redirect()->route('correspondencias.index')->with('success', 'Correspondencia eliminada correctamente.');
    }
}
