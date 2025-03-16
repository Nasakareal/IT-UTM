<?php

namespace App\Http\Controllers;

use App\Models\Subsection;
use App\Models\Modulo;
use Illuminate\Http\Request;

class SubsectionController extends Controller
{
    // Lista todas las subsecciones
    public function index()
    {
        $subsections = Subsection::with('modulo', 'parent')->get();
        return view('settings.subsections.index', compact('subsections'));
    }

    // Muestra el formulario para crear una nueva subsección
    public function create()
    {
        // Se obtienen todos los módulos para elegir a cuál asignar la subsección
        $modulos = Modulo::all();
        // Se obtienen las subsecciones existentes para, opcionalmente, seleccionar un padre
        $subsections = Subsection::all();
        return view('subsections.create', compact('modulos', 'subsections'));
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

    // Muestra el detalle de una subsección (podrías cargar sus hijos si existieran)
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
        // Para evitar asignar la subsección como padre de sí misma, se excluye su propio id
        $subsections = Subsection::where('id', '!=', $subsection->id)->get();
        return view('subsections.edit', compact('subsection', 'modulos', 'subsections'));
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
