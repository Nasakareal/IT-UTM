<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        $data['slug'] = Str::slug($data['nombre']);
        $i = 1;
        $baseSlug = $data['slug'];
        while (Seccion::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $baseSlug . '-' . $i++;
        }

        Seccion::create($data);

        return redirect()->route('secciones.index')->with('success', 'Sección creada correctamente.');
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

        $data['slug'] = Str::slug($data['nombre']);
        $i = 1;
        $baseSlug = $data['slug'];
        while (Seccion::where('slug', $data['slug'])->where('id', '!=', $seccion->id)->exists()) {
            $data['slug'] = $baseSlug . '-' . $i++;
        }

        $seccion->update($data);

        return redirect()->route('secciones.index')->with('success', 'Sección actualizada correctamente.');
    }


    public function destroy(Seccion $seccion)
    {
        $seccion->delete();

        return redirect()->route('secciones.index')->with('success', 'Sección eliminada correctamente.');
    }
}
