<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use App\Models\Subsection;
use Illuminate\Http\Request;

class ModuloController extends Controller
{
    // Muestra la lista de módulos
    public function index()
    {
        $modulos = Modulo::all();
        return view('settings.modulos.index', compact('modulos'));
    }

    // Muestra el formulario para crear un nuevo módulo
    public function create()
    {
        $secciones = \App\Models\Seccion::all();
        return view('settings.modulos.create', compact('secciones'));
    }

    // Almacena un nuevo módulo en la base de datos
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'      => 'required|string|max:255',
            'anio'        => 'nullable|string|max:10',
            'categoria'   => 'required|string|max:255',
            'color'       => 'nullable|string|max:7',
            'descripcion' => 'nullable|string',
            'link'        => 'nullable|url',
            'seccion_id'  => 'required|exists:seccions,id'
        ]);

        Modulo::create($data);

        return redirect()->route('modulos.index')->with('success', 'Módulo creado correctamente.');
    }

    // Muestra un módulo en detalle
    public function show(Modulo $modulo)
    {
        $subsections = Subsection::where('modulo_id', $modulo->id)
            ->whereNull('parent_id')
            ->with([
                'carpetas' => function ($query) {
                    $query->whereNull('parent_id')
                          ->with(['archivos', 'children']);
                },
                'submodulos' => function ($query) {
                    $query->with(['archivos' => function($q) {
                        $q->where('user_id', auth()->id());
                    }]);
                }
            ])
            ->get();

        return view('modulos.show', [
            'modulo' => $modulo,
            'subnivelesPrincipales' => $subsections,
        ]);
    }

    // Muestra el formulario para editar un módulo
    public function edit(Modulo $modulo)
    {
        $secciones = \App\Models\Seccion::all();
        return view('settings.modulos.edit', compact('modulo', 'secciones'));
    }

    // Actualiza el módulo en la base de datos
    public function update(Request $request, Modulo $modulo)
    {
        $data = $request->validate([
            'titulo'      => 'required|string|max:255',
            'anio'        => 'nullable|string|max:10',
            'categoria'   => 'required|string|max:255',
            'color'       => 'nullable|string|max:7',
            'descripcion' => 'nullable|string',
            'link'        => 'nullable|url',
            'seccion_id'  => 'required|exists:seccions,id'
        ]);

        $modulo->update($data);

        return redirect()->route('modulos.index')->with('success', 'Módulo actualizado correctamente.');
    }

    // Elimina el módulo de la base de datos
    public function destroy(Modulo $modulo)
    {
        $modulo->delete();

        return redirect()->route('modulos.index')->with('success', 'Módulo eliminado correctamente.');
    }
}
