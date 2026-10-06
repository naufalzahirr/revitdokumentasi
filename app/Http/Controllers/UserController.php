<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AccountPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', ['users' => User::orderBy('username')->paginate(20)]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(['role' => 'editor', 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        User::create($data + ['email' => Str::uuid().'@revita.invalid', 'must_change_password' => true]);

        return redirect()->route('users.index')->with('success', 'Akun berhasil dibuat. Pengguna wajib mengganti password saat pertama masuk.');
    }

    public function edit(User $user): View
    {
        return view('users.form', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        DB::transaction(function () use ($request, $user, $data) {
            $admins = User::where('role', 'admin')->where('is_active', true)->lockForUpdate()->get();
            if ($user->is($request->user()) && ($data['role'] !== 'admin' || ! $data['is_active'])) {
                throw ValidationException::withMessages(['role' => 'Anda tidak dapat menonaktifkan atau menurunkan peran akun admin sendiri.']);
            }
            if ($user->isAdmin() && $user->is_active && ($data['role'] !== 'admin' || ! $data['is_active']) && $admins->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'Harus tersedia minimal satu akun admin aktif.']);
            }
            if ($user->is_active !== (bool) $data['is_active']) {
                $user->auth_version++;
            }
            $user->fill($data)->save();
        });

        return redirect()->route('users.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return redirect()->route('account.password');
        }
        $data = $request->validate(['password' => AccountPassword::rules()], [
            'password.required' => 'Password baru wajib diisi.', 'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ] + AccountPassword::messages());
        $user->forceFill(['password' => $data['password'], 'must_change_password' => true, 'auth_version' => $user->auth_version + 1])->save();

        return redirect()->route('users.edit', $user)->with('success', 'Password berhasil direset. Sesi lama berakhir dan pengguna wajib mengganti password saat masuk kembali.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        if (is_string($request->input('username'))) {
            $request->merge(['username' => Str::lower(trim($request->input('username')))]);
        }
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9._-]*$/', Rule::unique('users', 'username')->ignore($user)],
            'role' => ['required', Rule::in(['admin', 'editor'])],
            'is_active' => ['required', 'boolean'],
        ];
        if (! $user) {
            $rules['password'] = AccountPassword::rules();
        }

        return $request->validate($rules, [
            'required' => ':attribute wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, titik, garis bawah, atau tanda hubung.',
            'role.in' => 'Peran akun tidak valid.',
            'is_active.boolean' => 'Status akun tidak valid.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ] + AccountPassword::messages(), ['name' => 'Nama', 'username' => 'Username', 'role' => 'Peran', 'is_active' => 'Status akun', 'password' => 'Password']);
    }
}
