<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        if (is_string($request->input('username'))) {
            $request->merge(['username' => Str::lower(trim($request->input('username')))]);
        }
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ], ['required' => ':attribute wajib diisi.'], ['username' => 'Username', 'password' => 'Password']);
        $key = 'login:'.hash('sha256', $data['username'].'|'.$request->ip());
        $ipKey = 'login-ip:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            $seconds = max(RateLimiter::availableIn($key), RateLimiter::availableIn($ipKey));
            throw ValidationException::withMessages(['username' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik."]);
        }
        if (! Auth::attempt($data + ['is_active' => true])) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, 60);
            throw ValidationException::withMessages(['username' => 'Username atau password tidak cocok, atau akun tidak aktif.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('auth_version', $request->user()->auth_version);
        // Bind the session to this password immediately, including the first login.
        $request->session()->put('password_hash_web', Auth::guard()->hashPasswordForCookie($request->user()->getAuthPassword()));

        return $request->user()->must_change_password
            ? redirect()->route('account.password')
            : redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }
}
