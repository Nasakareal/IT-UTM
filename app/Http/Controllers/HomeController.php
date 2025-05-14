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
        $user = Auth::user();

        $documentosPendientes = collect();

        if (!$user->hasRole('Administrador')) {
            $documentosPendientes = Submodulo::whereNotNull('fecha_limite')
                ->where(function ($query) use ($user) {
                    $query->whereDoesntHave('submoduloUsuarios', function ($q) use ($user) {
                            $q->where('user_id', $user->id)
                              ->where('estatus', 'entregado');
                        })
                        ->orWhereHas('submoduloUsuarios', function ($q) use ($user) {
                            $q->where('user_id', $user->id)
                              ->where('estatus', 'pendiente');
                        });
                })
                ->where(function ($query) {
                    $query->whereNull('fecha_apertura')
                          ->orWhere('fecha_apertura', '<=', now());
                })
                ->get();
        }

        $comunicados = Comunicado::latest()->get();
        $secciones = Seccion::with('modulos')->get();

        return view('home', compact('documentosPendientes', 'comunicados', 'secciones'));
    }

}
