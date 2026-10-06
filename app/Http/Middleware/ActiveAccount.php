<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->is_active || (int) $request->session()->get('auth_version', 0) !== (int) $request->user()->auth_version) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => 'Akun tidak dapat digunakan. Hubungi admin.']);
        }

        return $next($request);
    }
}
