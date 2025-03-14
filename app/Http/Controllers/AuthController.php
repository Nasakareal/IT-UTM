<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Validar los datos ingresados
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Intentar autenticar al usuario
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {
            return redirect()->intended('/home');
        }

        throw ValidationException::withMessages([
            'email' => ['Las credenciales no coinciden con nuestros registros.']
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}
