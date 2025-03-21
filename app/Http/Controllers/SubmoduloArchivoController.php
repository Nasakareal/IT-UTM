<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubmoduloArchivo;
use App\Models\Submodulo;
use Illuminate\Support\Facades\Storage;

class SubmoduloArchivoController extends Controller
{
    // Almacena un nuevo archivo asociado a un submódulo
    public function store(Request $request, $submodulo_id)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:2048'
        ]);

        $submodulo = Submodulo::findOrFail($submodulo_id);

        // Guardar el archivo en storage
        $path = $request->file('archivo')->store('submodulos', 'public');

        // Guardar en la base de datos
        SubmoduloArchivo::create([
            'submodulo_id' => $submodulo->id,
            'nombre' => $request->file('archivo')->getClientOriginalName(),
            'ruta' => $path
        ]);

        return redirect()->route('submodulos.show', $submodulo_id)
            ->with('success', 'Archivo subido correctamente.');
    }

    // Descarga un archivo
    public function download($id)
    {
        $archivo = SubmoduloArchivo::findOrFail($id);
        return Storage::download('public/' . $archivo->ruta, $archivo->nombre);
    }

    // Elimina un archivo
    public function destroy($id)
    {
        $archivo = SubmoduloArchivo::findOrFail($id);
        Storage::delete('public/' . $archivo->ruta);
        $archivo->delete();

        return back()->with('success', 'Archivo eliminado correctamente.');
    }
}
