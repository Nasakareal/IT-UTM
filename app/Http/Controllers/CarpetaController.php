<?php

namespace App\Http\Controllers;

use App\Models\Carpeta;
use App\Models\Archivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarpetaController extends Controller
{
    // 1. Listar solo las carpetas raíz
    public function index()
    {
        $carpetas = Carpeta::all();

        return view('settings.carpetas.index', compact('carpetas'));
    }


    // 2. Mostrar el formulario para crear una nueva carpeta
    public function create()
    {
        $allCarpetas = Carpeta::all();
        $subsections = \App\Models\Subsection::all();
        return view('settings.carpetas.create', compact('allCarpetas', 'subsections'));
    }


    // 3. Guardar la carpeta en la base de datos
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'         => 'required|string|max:255',
            'color'          => 'nullable|string|max:7',
            'parent_id'      => 'nullable|exists:carpetas,id',
            'subsection_id'  => 'nullable|exists:subsections,id',
        ]);

        Carpeta::create($data);

        return redirect()->route('carpetas.index')->with('success', 'Carpeta creada correctamente.');
    }

    // 4. Mostrar una carpeta junto con sus subcarpetas y archivos
    public function show(Carpeta $carpeta)
    {
        $subcarpetas = $carpeta->children;
        $archivos = $carpeta->archivos;
        return view('carpetas.show', compact('carpeta', 'subcarpetas', 'archivos'));
    }



    // 5. Mostrar el formulario para editar una carpeta existente
    public function edit(Carpeta $carpeta)
    {
        $allCarpetas = Carpeta::where('id', '!=', $carpeta->id)->get();
        $subsections = \App\Models\Subsection::all();

        return view('settings.carpetas.edit', compact('carpeta', 'allCarpetas', 'subsections'));
    }


    // 6. Actualizar la carpeta en la base de datos
    public function update(Request $request, Carpeta $carpeta)
    {
        $data = $request->validate([
            'nombre'         => 'required|string|max:255',
            'color'          => 'nullable|string|max:7',
            'parent_id'      => 'nullable|exists:carpetas,id',
            'subsection_id'  => 'nullable|exists:subsections,id',
        ]);

        $carpeta->update($data);

        return redirect()->route('carpetas.index')->with('success', 'Carpeta actualizada correctamente.');
    }

    // 7. Eliminar una carpeta
    public function destroy(Carpeta $carpeta)
    {
        $carpeta->delete();
        return redirect()->route('carpetas.index')->with('success', 'Carpeta eliminada correctamente.');
    }

    // 8. Guardar un archivo en la carpeta actual
    public function storeArchivo(Request $request, Carpeta $carpeta)
    {
        $data = $request->validate([
            'nombre'  => 'required|string|max:255',
            'archivo' => 'required|file|mimes:pdf,doc,docx,jpg,png',
        ]);

        $ruta = $request->file('archivo')->store('public/archivos');

        Archivo::create([
            'nombre'     => $data['nombre'],
            'ruta'       => $ruta,
            'carpeta_id' => $carpeta->id,
        ]);

        return back()->with('success', 'Archivo subido correctamente.');
    }

    public function upload(Request $request, Carpeta $carpeta)
    {
        $request->validate([
            'archivo' => 'required|file|max:20480',
        ]);

        $path = $request->file('archivo')->store("carpetas/{$carpeta->id}", 'public');

        Archivo::create([
            'nombre' => $request->file('archivo')->getClientOriginalName(),
            'ruta' => $path,
            'carpeta_id' => $carpeta->id,
        ]);

        return redirect()->route('carpetas.index')->with('success', 'Archivo subido correctamente.');
    }

}
