<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPasswordChanged
{
    public function handle($request, Closure $next)
    {
        if (auth()->check() && auth()->user()->must_change_password && !$request->is('settings/change-password')) {
            return redirect()->route('password.change.form');
        }

        return $next($request);
    }
}
