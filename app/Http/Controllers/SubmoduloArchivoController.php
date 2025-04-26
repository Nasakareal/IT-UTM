<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubmoduloArchivo;
use App\Models\Submodulo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubmoduloArchivoController extends Controller
{
    public function store(Request $request, $submodulo_id)
    {
        $request->validate([
            'archivo'     => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:2048',
            'firma_sat'   => 'nullable|string',
        ]);

        $submodulo = Submodulo::findOrFail($submodulo_id);
        $path = $request->file('archivo')->store('submodulos', 'public');

        $data = [
            'submodulo_id' => $submodulo->id,
            'user_id'      => Auth::id(),
            'nombre'       => $request->file('archivo')->getClientOriginalName(),
            'ruta'         => $path,
        ];

        if ($request->filled('firma_sat')) {
            $data['firma_sat']  = $request->input('firma_sat');
            $data['fecha_firma'] = Carbon::now();
        }

        SubmoduloArchivo::create($data);

        return redirect()
            ->route('submodulos.show', $submodulo_id)
            ->with('success', 'Archivo subido correctamente.');
    }

    public function download($id)
    {
        $archivo = SubmoduloArchivo::findOrFail($id);
        return Storage::download('public/' . $archivo->ruta, $archivo->nombre);
    }

    public function destroy($id)
    {
        $archivo = SubmoduloArchivo::findOrFail($id);
        Storage::delete('public/' . $archivo->ruta);
        $archivo->delete();

        return back()->with('success', 'Archivo eliminado correctamente.');
    }
}
