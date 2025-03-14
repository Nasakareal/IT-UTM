<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Requerimiento;
use App\Models\Comunicado;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Obtener los requerimientos pendientes del usuario autenticado
        $documentosPendientes = Requerimiento::where('user_id', auth()->id())
                                    ->where('estado', 'pendiente')
                                    ->get();

        // Obtener los comunicados, ordenados del más reciente al más antiguo
        $comunicados = Comunicado::latest()->get();

        return view('home', compact('documentosPendientes', 'comunicados'));
    }
}
