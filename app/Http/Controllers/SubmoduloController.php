<?php

namespace App\Http\Controllers;

use App\Models\Submodulo;
use App\Models\SubmoduloArchivo;
use App\Models\Subsection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubmoduloController extends Controller
{
    public function index()
    {
        $submodulos = Submodulo::with('subsection')->get();
        return view('settings.submodulos.index', compact('submodulos'));
    }

    public function create()
    {
        $subsections = Subsection::all();
        return view('settings.submodulos.create', compact('subsections'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'        => 'required|string|max:255',
            'descripcion'   => 'nullable|string',
            'fecha_limite'  => 'nullable|date',
            'subsection_id' => 'required|exists:subsections,id'
        ]);

        Submodulo::create($data);

        return redirect()->route('submodulos.index')
                         ->with('success', 'Submódulo creado correctamente.');
    }

    public function show(Submodulo $submodulo)
    {
        return view('submodulos.show', compact('submodulo'));
    }

    public function edit(Submodulo $submodulo)
    {
        $subsections = Subsection::all();
        return view('settings.submodulos.edit', compact('submodulo', 'subsections'));
    }

    public function update(Request $request, Submodulo $submodulo)
    {
        $data = $request->validate([
            'titulo'        => 'required|string|max:255',
            'descripcion'   => 'nullable|string',
            'fecha_limite'  => 'nullable|date',
            'subsection_id' => 'required|exists:subsections,id'
        ]);

        $submodulo->update($data);

        return redirect()->route('submodulos.index')
                         ->with('success', 'Submódulo actualizado correctamente.');
    }

    public function destroy(Submodulo $submodulo)
    {
        $submodulo->delete();

        return redirect()->route('submodulos.index')
                         ->with('success', 'Submódulo eliminado correctamente.');
    }

    public function subirArchivos(Request $request)
    {
        $request->validate([
            'submodulo_id'         => 'required|exists:submodulos,id',
            'oficio_entrega'       => 'required|file|mimes:pdf|max:2048',
            'programa_austeridad'  => 'required|file|mimes:pdf|max:12288',
        ]);

        $submodulo = Submodulo::findOrFail($request->submodulo_id);

        if ($request->hasFile('oficio_entrega')) {
            $pathOficio = $request->file('oficio_entrega')->store('submodulos', 'public');

            SubmoduloArchivo::create([
                'submodulo_id' => $submodulo->id,
                'user_id'      => Auth::id(),
                'nombre'       => 'oficio_entrega',
                'ruta'         => $pathOficio,
            ]);
        }

        if ($request->hasFile('programa_austeridad')) {
            $pathPrograma = $request->file('programa_austeridad')->store('submodulos', 'public');

            SubmoduloArchivo::create([
                'submodulo_id' => $submodulo->id,
                'user_id'      => Auth::id(),
                'nombre'       => 'programa_austeridad',
                'ruta'         => $pathPrograma,
            ]);
        }

        $submodulo->acuse_pdf = 'submodulos/acuse_ejemplo.pdf';
        $submodulo->save();

        $acuseUrl = asset('storage/' . $submodulo->acuse_pdf);

        return response()->json([
            'success'   => true,
            'acuse_url' => $acuseUrl,
        ]);
    }

    public function archivosUsuario($id)
    {
        $submodulo = Submodulo::with(['archivos' => function($q) {
            $q->where('user_id', Auth::id());
        }])->findOrFail($id);

        $oficio = $submodulo->archivos->where('nombre', 'oficio_entrega')->first();
        $programa = $submodulo->archivos->where('nombre', 'programa_austeridad')->first();

        if (!$oficio && $submodulo->oficio_pdf) {
            $oficio = (object)[ 'ruta' => $submodulo->oficio_pdf ];
        }
        if (!$programa && $submodulo->programa_pdf) {
            $programa = (object)[ 'ruta' => $submodulo->programa_pdf ];
        }

        $acuseUrl = $submodulo->acuse_pdf ? asset('storage/' . $submodulo->acuse_pdf) : null;

        return response()->json([
            'oficio_url'   => $oficio ? asset('storage/' . $oficio->ruta) : null,
            'programa_url' => $programa ? asset('storage/' . $programa->ruta) : null,
            'acuse_url'    => $acuseUrl,
        ]);
    }
}
