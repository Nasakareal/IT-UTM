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
    public function show(User $user)
    {
        $documentos = DocumentoSubido::where('user_id', $user->id)->get();

        return view('settings.documentos-profesores.show', [
            'profesor' => $user,
            'documentos' => $documentos,
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
