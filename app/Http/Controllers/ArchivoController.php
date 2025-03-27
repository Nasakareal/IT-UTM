<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Carpeta;
use Illuminate\Http\Request;

class ArchivoController extends Controller
{
    public function index()
    {
        $archivos = Archivo::all();
        $carpetas = Carpeta::all();

        return view('settings.archivos.index', compact('archivos', 'carpetas'));

    }
    
    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Archivo $archivo)
    {
        //
    }

    public function edit(Archivo $archivo)
    {
        //
    }

    public function update(Request $request, Archivo $archivo)
    {
        //
    }

    public function destroy(Archivo $archivo)
    {
        $archivo->delete();

        return redirect()->route('archivos.index')->with('success', 'Archivo eliminado correctamente.');
    }
}
