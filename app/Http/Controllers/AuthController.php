<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function loginForm()
    {
        return view('auth.login');
    }

    /**
     * Procesa el inicio de sesión del usuario.
     */
    public function login(Request $request)
    {
        // Validar el formulario
        $data = $request->validate([
            'correo_institucional' => 'required|email',
            'password'             => 'required|string',
        ]);

        // Intentar autenticar
        if (Auth::attempt([
                'correo_institucional' => $data['correo_institucional'],
                'password'             => $data['password'],
            ], $request->filled('remember'))
        ) {
            // Regenerar la sesión para evitar fijación
            $request->session()->regenerate();

            $user = Auth::user();

            // Verificar si debe cambiar su contraseña
            if ($user->must_change_password) {
                return redirect()->route('password.change.form');
            }

            return redirect()->intended('/home');
        }

        // Si falla, lanza excepción con mensaje
        throw ValidationException::withMessages([
            'correo_institucional' => ['Las credenciales no coinciden con nuestros registros.'],
        ]);
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Invalidar y regenerar token CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
