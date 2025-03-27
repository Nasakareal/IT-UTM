<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Submodulo;
use App\Models\Comunicado;
use App\Models\Seccion;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $userId = Auth::id();

        $documentosPendientes = Submodulo::whereNotNull('fecha_limite')
            ->where(function ($query) use ($userId) {
                $query->whereDoesntHave('submoduloUsuarios', function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->where('estatus', 'entregado');
                })
                ->orWhereHas('submoduloUsuarios', function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->where('estatus', 'pendiente');
                });
            })->get();

        $comunicados = Comunicado::latest()->get();
        $secciones = Seccion::with('modulos')->get();

        return view('home', compact('documentosPendientes', 'comunicados', 'secciones'));
    }
}
