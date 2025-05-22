<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use Illuminate\Http\Request;

class SeccionController extends Controller
{
    public function index()
    {
        $secciones = Seccion::all();
        return view('settings.secciones.index', compact('secciones'));
    }

    public function sort(Request $request)
    {
        foreach ($request->orden as $item) {
            \App\Models\Seccion::where('id', $item['id'])->update(['orden' => $item['orden']]);
        }

        return response()->json(['success' => true]);
    }

    public function create()
    {
        $secciones = \App\Models\Seccion::all();
        return view('settings.secciones.create', compact('secciones'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255'
        ]);

        $data['slug'] = 'generico';

        Seccion::create($data);

        return redirect()->route('secciones.index')->with('success', 'Sección creado correctamente.');
    }

    public function show(Seccion $seccion)
    {
        //
    }

    public function edit(Seccion $seccion)
    {
        return view('settings.secciones.edit', compact('seccion'));
    }


    public function update(Request $request, Seccion $seccion)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255'
        ]);

        $data['slug'] = 'generico';

        $seccion->update($data);

        return redirect()->route('secciones.index')->with('success', 'Sección actualizada correctamente.');
    }


    public function destroy(Seccion $seccion)
    {
        $seccion->delete();

        return redirect()->route('secciones.index')->with('success', 'Sección eliminada correctamente.');
    }
}
