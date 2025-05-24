<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Muestra el panel de configuración si tiene permiso.
     * Caso contrario, lo redirige al cambio de contraseña.
     */
    public function index()
    {
        $user = Auth::user();

        if (! $user->can('ver configuraciones')) {
            return redirect()->route('password.change.form');
        }

        $settings = [];
        return view('settings.index', compact('settings'));
    }
}
