<?php

namespace App\Http\Controllers;

use App\Models\Submodulo;
use App\Models\SubmoduloArchivo;
use App\Models\SubmoduloUsuario;
use App\Models\Subsection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubmoduloController extends Controller
{
    public function index()
    {
        Submodulo::whereNotNull('fecha_cierre')
                  ->where('fecha_cierre', '<', Carbon::now())
                  ->where('estatus', '!=', 'Incumplimiento')
                  ->update(['estatus' => 'Incumplimiento']);

        // 2) Carga la lista
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
            'subsection_id'        => 'required|exists:subsections,id',
            'titulo'               => 'required|string|max:125',
            'descripcion'          => 'nullable|string',
            'documento_solicitado' => 'required|file|mimes:pdf,doc,docx|max:2048',
            'fecha_apertura'       => 'nullable|date|before_or_equal:fecha_cierre',
            'fecha_limite'         => 'nullable|date',
            'fecha_cierre'         => 'nullable|date|after_or_equal:fecha_apertura',
        ]);

        // guarda el archivo en disco y sobreescribe el campo con la ruta
        if ($request->hasFile('documento_solicitado')) {
            $ruta = $request->file('documento_solicitado')
                            ->store('plantillas', 'public');
            $data['documento_solicitado'] = $ruta;
        }

        // inyecta estatus por defecto
        $data['estatus']   = 'pendiente';
        $data['acuse_pdf'] = null;

        $submodulo = Submodulo::create($data);

        // chequeo de vencimiento
        if ($submodulo->fecha_cierre && now()->gt($submodulo->fecha_cierre)) {
            $submodulo->update(['estatus' => 'Incumplimiento']);
        }

        return redirect()
            ->route('submodulos.index')
            ->with('success', 'Submódulo creado correctamente.');
    }



    public function show(Submodulo $submodulo)
    {
        return view('settings.submodulos.show', compact('submodulo'));
    }

    public function edit(Submodulo $submodulo)
    {
        $subsections = Subsection::all();
        return view('settings.submodulos.edit', compact('submodulo', 'subsections'));
    }

    public function update(Request $request, Submodulo $submodulo)
    {
        $data = $request->validate([
            'subsection_id'        => 'required|exists:subsections,id',
            'titulo'               => 'required|string|max:125',
            'descripcion'          => 'nullable|string',
            'documento_solicitado' => 'nullable|string|max:125',
            'fecha_apertura'       => 'nullable|date|before_or_equal:fecha_cierre',
            'fecha_limite'         => 'nullable|date',
            'fecha_cierre'         => 'nullable|date|after_or_equal:fecha_apertura',
        ]);

        $submodulo->update($data);

        if ($submodulo->fecha_cierre && now()->gt($submodulo->fecha_cierre)) {
            $submodulo->update(['estatus' => 'Incumplimiento']);
        }

        return redirect()
            ->route('submodulos.index')
            ->with('success', 'Submódulo actualizado correctamente.');
}


    public function destroy(Submodulo $submodulo)
    {
        $submodulo->delete();
        return redirect()
            ->route('submodulos.index')
            ->with('success', 'Submódulo eliminado correctamente.');
    }

    public function subirArchivos(Request $request)
    {
        $request->validate([
            'submodulo_id'        => 'required|exists:submodulos,id',
            'oficio_entrega'      => 'nullable|file|mimes:pdf|max:2048',
            'programa_austeridad' => 'nullable|file|mimes:pdf|max:12288',
        ]);

        $submodulo = Submodulo::findOrFail($request->submodulo_id);

        // Oficio de entrega
        if ($request->hasFile('oficio_entrega')) {
            $path = $request->file('oficio_entrega')->store('submodulos', 'public');
            SubmoduloArchivo::create([
                'submodulo_id' => $submodulo->id,
                'user_id'      => Auth::id(),
                'nombre'       => 'oficio_entrega',
                'ruta'         => $path,
            ]);
        }

        // Programa de austeridad
        if ($request->hasFile('programa_austeridad')) {
            $path = $request->file('programa_austeridad')->store('submodulos', 'public');
            SubmoduloArchivo::create([
                'submodulo_id' => $submodulo->id,
                'user_id'      => Auth::id(),
                'nombre'       => 'programa_austeridad',
                'ruta'         => $path,
            ]);
        }

        // Marca usuario como “Entregado”
        $submoduloUsuario = SubmoduloUsuario::updateOrCreate(
            [
                'user_id'      => Auth::id(),
                'submodulo_id' => $submodulo->id,
            ],
            [
                'estatus' => 'Entregado',
            ]
        );

        // Devuelve JSON con URLs
        $acuseUrl = $submodulo->acuse_pdf
            ? asset('storage/' . $submodulo->acuse_pdf)
            : null;

        return response()->json([
            'success'   => true,
            'acuse_url' => $acuseUrl,
            'estatus'   => $submoduloUsuario->estatus,
        ]);
    }

    public function archivosUsuario($id)
    {
        $submodulo = Submodulo::with(['archivos' => function($q) {
            $q->where('user_id', Auth::id());
        }])->findOrFail($id);

        $oficio   = $submodulo->archivos->firstWhere('nombre', 'oficio_entrega');
        $programa = $submodulo->archivos->firstWhere('nombre', 'programa_austeridad');

        $acuseUrl = $submodulo->acuse_pdf
            ? asset('storage/' . $submodulo->acuse_pdf)
            : null;

        return response()->json([
            'oficio_url'   => $oficio   ? asset('storage/' . $oficio->ruta)   : null,
            'programa_url' => $programa ? asset('storage/' . $programa->ruta) : null,
            'acuse_url'    => $acuseUrl,
        ]);
    }
}
