<?php

namespace App\Http\Controllers;

use App\Models\Comunicado;
use Illuminate\Http\Request;

class ComunicadoController extends Controller
{
    public function index()
    {
        $comunicados = Comunicado::all();
        return view('settings.comunicados.index', compact('comunicados'));
    }

    public function create()
    {
        $allComunicados = Comunicado::all();
        return view('settings.comunicados.create', compact('allComunicados'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'nullable|string',
            'tipo' => 'nullable|string|max:125',
            'ruta_imagen' => 'nullable|string|max:125',
        ]);

        $data['contenido'] = strip_tags($data['contenido'], '<b><strong><i><em><u><p><br><ul><ol><li>');

        Comunicado::create($data);

        return redirect()->route('comunicados.index')->with('success', 'Comunicado creado correctamente.');
    }

    public function show(Comunicado $comunicado)
    {
        //
    }

    public function edit(Comunicado $comunicado)
    {
        return view('settings.comunicados.edit', compact('comunicado'));
    }

    public function update(Request $request, Comunicado $comunicado)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'nullable|string',
            'tipo' => 'nullable|string|max:125',
            'ruta_imagen' => 'nullable|string|max:125',
        ]);

        $data['contenido'] = strip_tags($data['contenido'], '<b><strong><i><em><u><p><br><ul><ol><li>');

        $comunicado->update($data);

        return redirect()->route('comunicados.index')->with('success', 'Comunicado actualizado correctamente.');
    }


    public function destroy(Comunicado $comunicado)
    {
        $comunicado->delete();

        return redirect()->route('comunicados.index')->with('success', 'Comunicado eliminado correctamente.');
    }

}
