<?php

namespace App\Http\Controllers;

use App\Models\Submodulo;
use App\Models\Subsection;
use Illuminate\Http\Request;

class SubmoduloController extends Controller
{
    // Muestra la lista de submódulos
    public function index()
    {
        $submodulos = Submodulo::with('subsection')->get();
        return view('settings.submodulos.index', compact('submodulos'));
    }

    // Muestra el formulario para crear un nuevo submódulo
    public function create()
    {
        $subsections = Subsection::all();
        return view('settings.submodulos.create', compact('subsections'));
    }

    // Almacena un nuevo submódulo en la base de datos
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'fecha_limite' => 'nullable|date',
            'subsection_id' => 'required|exists:subsections,id'
        ]);

        Submodulo::create($data);

        return redirect()->route('submodulos.index')->with('success', 'Submódulo creado correctamente.');
    }

    // Muestra un submódulo en detalle
    public function show(Submodulo $submodulo)
    {
        return view('submodulos.show', compact('submodulo'));
    }

    // Muestra el formulario para editar un submódulo
    public function edit(Submodulo $submodulo)
    {
        $subsections = Subsection::all();
        return view('settings.submodulos.edit', compact('submodulo', 'subsections'));
    }

    // Actualiza el submódulo en la base de datos
    public function update(Request $request, Submodulo $submodulo)
    {
        $data = $request->validate([
            'titulo'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'fecha_limite' => 'nullable|date',
            'subsection_id' => 'required|exists:subsections,id'
        ]);

        $submodulo->update($data);

        return redirect()->route('submodulos.index')->with('success', 'Submódulo actualizado correctamente.');
    }

    // Elimina el submódulo de la base de datos
    public function destroy(Submodulo $submodulo)
    {
        $submodulo->delete();

        return redirect()->route('submodulos.index')->with('success', 'Submódulo eliminado correctamente.');
    }
}
