<?php

namespace App\Http\Controllers;

use App\Support\AccountPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(): View
    {
        return view('auth.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => AccountPassword::rules(),
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak cocok.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.min' => 'Password baru minimal 8 karakter.',
        ] + AccountPassword::messages());
        if (Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Gunakan password baru yang berbeda dari password saat ini.']);
        }
        $request->user()->forceFill(['password' => $data['password'], 'must_change_password' => false, 'auth_version' => $request->user()->auth_version + 1])->save();
        $request->session()->regenerate();
        $request->session()->put('auth_version', $request->user()->auth_version);
        $request->session()->put('password_hash_web', Auth::guard()->hashPasswordForCookie($request->user()->getAuthPassword()));

        return redirect()->intended(route('home'))->with('success', 'Password berhasil diganti. Gunakan password baru untuk login berikutnya.');
    }
}
