<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UyeAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Giriş yapılmamışsa giriş sayfasına yönlendir
        if (!Auth::guard('uye')->check()) {
            return redirect()->route('uye.giris.form');
        }

        return $next($request);
    }
}
