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
            'archivo'    => 'required|file|mimes:pdf,doc,docx,xls,xlsx,odt,txt|max:2048',
            'firma_sat'  => 'nullable|string',
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
            $data['firma_sat']   = $request->input('firma_sat');
            $data['fecha_firma'] = Carbon::now();
        }

        $registro = SubmoduloArchivo::create($data);

        /** 🔹 GENERAR ACUSE PDF AUTOMÁTICO */
        if (isset($data['firma_sat'])) {
            $usuario = Auth::user();
            $certName = $usuario->name ?? 'Usuario desconocido';
            $certRFC  = $usuario->rfc ?? 'N/A';
            $hash     = hash_file('sha256', storage_path('app/public/' . $path));

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.acuse', [  // <== usa 'acuse', no 'acuse_individual'
                'tituloAcuse' => 'Acuse de recepción del submódulo "' . $submodulo->titulo . '" correspondiente al cuatrimestre Mayo–Agosto 2025.',
                'materia'     => $submodulo->titulo,
                'tipo'        => 'Oficio Entrega',
                'usuario'     => $certName,
                'rfc'         => $certRFC,
                'fecha_firma' => $data['fecha_firma']->format('Y-m-d H:i:s'),
                'hashArchivo' => $hash,
                'programa'    => $submodulo->subsection->modulo->nombre ?? 'SIN PROGRAMA',
                'atentamente' => 'Universidad Tecnológica de Morelia',
            ])->setPaper('letter');


            $acuseRel = 'acuses/acuse_' . $registro->id . '.pdf';
            Storage::disk('public')->put($acuseRel, $pdf->output());
            $registro->update(['acuse_pdf' => $acuseRel]);
        }

        return redirect()
            ->route('submodulos.show', $submodulo_id)
            ->with('success', 'Archivo subido y acuse generado correctamente.');
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
