<?php

namespace App\Http\Controllers;

use App\Models\Submodulo;
use App\Models\SubmoduloArchivo;
use App\Models\SubmoduloUsuario;
use App\Models\Subsection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SubmoduloController extends Controller
{
    public function index()
    {
        // Vence automáticamente los submódulos pasados de fecha_cierre
        Submodulo::whereNotNull('fecha_cierre')
            ->where('fecha_cierre', '<', Carbon::now())
            ->where('estatus', '!=', 'Incumplimiento')
            ->update(['estatus' => 'Incumplimiento']);

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

        // Guarda la plantilla base y almacena la ruta
        if ($request->hasFile('documento_solicitado')) {
            $data['documento_solicitado'] = $request->file('documento_solicitado')
                ->store('plantillas', 'public');
        }

        // Inyecta valores por defecto
        $data['estatus']   = 'pendiente';
        $data['acuse_pdf'] = null;

        $submodulo = Submodulo::create($data);

        // Forzar Incumplimiento si ya venció
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
            'titulo'               => 'required|string|max:2048',
            'descripcion'          => 'nullable|string',
            'documento_solicitado' => 'required|file|mimes:pdf,doc,docx|max:8048',
            'fecha_apertura'       => 'nullable|date|before_or_equal:fecha_cierre',
            'fecha_limite'         => 'nullable|date',
            'fecha_cierre'         => 'nullable|date|after_or_equal:fecha_apertura',
        ]);

        $submodulo->update($data);

        // Revisa vencimiento tras actualización
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

    /**
     * Sube oficio, programa y realiza firma electrónica con e.firma SAT.
     */
    public function subirArchivos(Request $request)
    {
        $request->validate([
            'submodulo_id'        => 'required|exists:submodulos,id',
            'oficio_entrega'      => 'nullable|file|mimes:pdf|max:2048',
            'programa_austeridad' => 'nullable|file|mimes:pdf|max:12288',
            'efirma_p12'          => 'required|file|max:1024',
            'efirma_pass'         => 'required|string',
        ]);

        $submodulo = Submodulo::findOrFail($request->submodulo_id);

        // 1) Guarda los PDFs
        if ($request->hasFile('oficio_entrega')) {
            $path = $request->file('oficio_entrega')->store('submodulos', 'public');
            SubmoduloArchivo::create([
                'submodulo_id' => $submodulo->id,
                'user_id'      => Auth::id(),
                'nombre'       => 'oficio_entrega',
                'ruta'         => $path,
            ]);
        }
        if ($request->hasFile('programa_austeridad')) {
            $path = $request->file('programa_austeridad')->store('submodulos', 'public');
            SubmoduloArchivo::create([
                'submodulo_id' => $submodulo->id,
                'user_id'      => Auth::id(),
                'nombre'       => 'programa_austeridad',
                'ruta'         => $path,
            ]);
        }

        // 2) Procesa e.firma SAT (.p12 + pass) y firma el último oficio_entrega
        $p12Contents = file_get_contents($request->file('efirma_p12')->getRealPath());

        if (! openssl_pkcs12_read($p12Contents, $certs, $request->efirma_pass)) {
            return response()->json([
                'success' => false,
                'message' => 'Certificado e.firma inválido o contraseña incorrecta.'
            ], 422);
        }

        $privKey = $certs['pkey'];
        $cert    = $certs['cert'];

        // 3) Toma el último oficio de entrega
        $archivo = SubmoduloArchivo::where('submodulo_id', $submodulo->id)
            ->where('nombre', 'oficio_entrega')
            ->latest()
            ->first();

        if ($archivo) {
            $origPath   = storage_path('app/public/' . $archivo->ruta);
            $signedPath = storage_path('app/public/submodulos/signed_' . $archivo->id . '.pdf');

            // Crea firma PKCS7 (DETACHED)
            openssl_pkcs7_sign(
                $origPath,
                $signedPath,
                $cert,
                $privKey,
                [], // Headers
                PKCS7_DETACHED
            );

            // Guarda firma base64 y fecha de firma
            $firmaSat   = base64_encode(file_get_contents($signedPath));
            $fechaFirma = Carbon::now();

            $archivo->update([
                'firma_sat'   => $firmaSat,
                'fecha_firma' => $fechaFirma,
            ]);
        }

        // 4) Marca usuario como "Entregado"
        SubmoduloUsuario::updateOrCreate(
            [
                'user_id'      => Auth::id(),
                'submodulo_id' => $submodulo->id,
            ],
            ['estatus' => 'Entregado']
        );

        // 5) Respuesta JSON
        return response()->json([
            'success'       => true,
            'submodulo_id'  => $submodulo->id,
            'fecha_firma'   => isset($fechaFirma) ? $fechaFirma->toDateTimeString() : null,
            'estatus'       => 'Entregado',
        ]);
    }


    /**
     * Devuelve URLs de los archivos subidos por el usuario en este submódulo.
     */
    public function archivosUsuario($id)
    {
        $submodulo = Submodulo::with(['archivos' => function($q) {
            $q->where('user_id', Auth::id());
        }])->findOrFail($id);

        $oficio   = $submodulo->archivos->firstWhere('nombre', 'oficio_entrega');
        $programa = $submodulo->archivos->firstWhere('nombre', 'programa_austeridad');

        return response()->json([
            'oficio_url'   => $oficio   ? asset('storage/' . $oficio->ruta)   : null,
            'programa_url' => $programa ? asset('storage/' . $programa->ruta) : null,
            'acuse_url'    => $submodulo->acuse_pdf
                            ? asset('storage/' . $submodulo->acuse_pdf)
                            : null,
        ]);
    }
}
