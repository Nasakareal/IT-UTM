<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DocumentoSubido;

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
}
