<?php

namespace App\Http\Controllers;

use App\Models\Carpeta;
use App\Models\Archivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarpetaController extends Controller
{
    // 1. Listar solo las carpetas raíz (donde parent_id es null)
    public function index()
    {
        $carpetas = Carpeta::whereNull('parent_id')->get();
        return view('settings.carpetas.index', compact('carpetas'));
    }

    // 2. Formulario para crear una carpeta (opcionalmente con selección de carpeta padre)
    public function create()
    {
        // Listar todas las carpetas para elegir padre (opcional)
        $allCarpetas = Carpeta::all();
        return view('settings.carpetas.create', compact('allCarpetas'));
    }

    // 3. Guardar la carpeta en la base de datos
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:255',
            'color'     => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:carpetas,id'
        ]);

        Carpeta::create($data);

        return redirect()->route('carpetas.index')->with('success', 'Carpeta creada correctamente.');
    }

    // 4. Mostrar una carpeta, junto con sus subcarpetas y archivos
    public function show(Carpeta $carpeta)
    {
        // Cargar las subcarpetas hijas
        $subcarpetas = $carpeta->children;
        // Cargar los archivos asociados a la carpeta
        $archivos = $carpeta->archivos;
        return view('carpetas.show', compact('carpeta', 'subcarpetas', 'archivos'));
    }

    // 5. Guardar un archivo en la carpeta actual
    public function storeArchivo(Request $request, Carpeta $carpeta)
    {
        $data = $request->validate([
            'nombre'  => 'required|string|max:255',
            'archivo' => 'required|file|mimes:pdf,doc,docx,jpg,png'
        ]);

        // Subir el archivo a la carpeta "public/archivos"
        $ruta = $request->file('archivo')->store('public/archivos');

        Archivo::create([
            'nombre'     => $data['nombre'],
            'ruta'       => $ruta,
            'carpeta_id' => $carpeta->id
        ]);

        return back()->with('success', 'Archivo subido correctamente.');
    }
}
