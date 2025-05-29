<?php

namespace App\Http\Controllers;

use App\Models\Subsection;
use App\Models\Modulo;
use Illuminate\Http\Request;

class SubsectionController extends Controller
{
    public function index()
    {
        $subsections = Subsection::with('modulo', 'parent')->get();
        return view('settings.subsections.index', compact('subsections'));
    }

    public function sort(Request $request)
    {
        foreach ($request->orden as $item) {
            \App\Models\Subsection::where('id', $item['id'])->update(['orden' => $item['orden']]);
        }

        return response()->json(['success' => true]);
    }

    public function create(Request $request)
    {
        $modulos = \App\Models\Modulo::all();
        $subsections = \App\Models\Subsection::all();
        $moduloSeleccionado = $request->query('modulo_id');

        return view('settings.subsections.create', [
            'modulos' => $modulos,
            'subsections' => $subsections,
            'moduloSeleccionado' => $moduloSeleccionado,
        ]);
    }



    // Almacena una nueva subsección en la base de datos
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'     => 'required|string|max:255',
            'modulo_id'  => 'required|exists:modulos,id',
            'parent_id'  => 'nullable|exists:subsections,id',
        ]);

        Subsection::create($data);

        return redirect()->route('subsections.index')->with('success', 'Subsección creada correctamente.');
    }

    // Muestra el detalle de una subsección
    public function show(Subsection $subsection)
    {
        // Cargamos las subsecciones hijas si se tiene definida la relación "children"
        $subsection->load('children');
        return view('subsections.show', compact('subsection'));
    }

    // Muestra el formulario para editar una subsección existente
    public function edit(Subsection $subsection)
    {
        $modulos = Modulo::all();
        $subsections = Subsection::where('id', '!=', $subsection->id)->get();
        return view('settings.subsections.edit', compact('subsection', 'modulos', 'subsections'));
    }

    // Actualiza una subsección en la base de datos
    public function update(Request $request, Subsection $subsection)
    {
        $data = $request->validate([
            'nombre'     => 'required|string|max:255',
            'modulo_id'  => 'required|exists:modulos,id',
            'parent_id'  => 'nullable|exists:subsections,id',
        ]);

        $subsection->update($data);

        return redirect()->route('subsections.index')->with('success', 'Subsección actualizada correctamente.');
    }

    // Elimina una subsección
    public function destroy(Subsection $subsection)
    {
        $subsection->delete();
        return redirect()->route('subsections.index')->with('success', 'Subsección eliminada correctamente.');
    }
}
