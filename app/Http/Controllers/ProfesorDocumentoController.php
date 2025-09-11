<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DocumentoSubido;
use Illuminate\Support\Facades\Storage;

class ProfesorDocumentoController extends Controller
{
    // Listado de profesores con teacher_id asignado
    public function index()
    {
        $profesores = User::whereNotNull('teacher_id')->get();

        return view('settings.documentos-profesores.index', compact('profesores'));
    }

    // Ver documentos de un profesor específico
     public function show(Request $request, User $user)
    {
        $quarters = DocumentoSubido::where('user_id', $user->id)
            ->selectRaw('COALESCE(NULLIF(TRIM(quarter_name), ""), "SIN CUATRIMESTRE") as q')
            ->selectRaw('MAX(created_at) as last_cap')
            ->groupBy('q')
            ->orderByDesc('last_cap')
            ->pluck('q');

        $quarterSel = trim((string) $request->input('quarter_name', ''));

        $documentos = DocumentoSubido::where('user_id', $user->id)
            ->when($quarterSel !== '', function ($q) use ($quarterSel) {
                if ($quarterSel === 'SIN CUATRIMESTRE') {
                    $q->where(function ($qq) {
                        $qq->whereNull('quarter_name')
                           ->orWhereRaw('TRIM(quarter_name) = ""');
                    });
                } else {
                    $q->whereRaw('TRIM(quarter_name) = ?', [$quarterSel]);
                }
            })
            ->orderByDesc('created_at')
            ->get();

        return view('settings.documentos-profesores.show', [
            'profesor'    => $user,
            'documentos'  => $documentos,
            'quarters'    => $quarters,
            'quarterSel'  => $quarterSel,
        ]);
    }

    // Eliminar documento y archivos relacionados
    public function destroy($id)
    {
        $documento = DocumentoSubido::findOrFail($id);

        // Eliminar archivo principal
        if ($documento->archivo && Storage::disk('public')->exists($documento->archivo)) {
            Storage::disk('public')->delete($documento->archivo);
        }

        // Eliminar acuse si existe
        if ($documento->acuse_pdf && Storage::disk('public')->exists($documento->acuse_pdf)) {
            Storage::disk('public')->delete($documento->acuse_pdf);
        }

        $documento->delete();

        return back()->with('success', 'Documento eliminado correctamente.');
    }
}
