@extends('layouts.app')
@section('title', $user->exists ? 'Kelola pengguna' : 'Tambah pengguna')
@section('breadcrumb', 'Pengguna')
@section('content')
<a class="back-link" href="{{ route('users.index') }}"><x-icon name="arrow" size="17"/>Kembali ke pengguna</a>
<div class="page-heading"><div><h1>{{ $user->exists ? 'Kelola pengguna' : 'Tambah pengguna' }}<span class="heading-dot">.</span></h1><p>Pengelola dapat mengelola semua nota dan kategori. Admin juga dapat mengelola akun.</p></div></div>
<section class="panel account-panel"><form action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" method="post">@csrf @if($user->exists)@method('PUT')@endif
    <div class="field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="100"></div>
    <div class="field"><label for="username">Username</label><input id="username" name="username" value="{{ old('username', $user->username) }}" required maxlength="50" autocomplete="off" autocapitalize="none" spellcheck="false"><small>Huruf kecil, angka, titik, garis bawah, atau tanda hubung.</small></div>
    <div class="fields-row"><div class="field"><label for="role">Peran</label><select id="role" name="role" required><option value="editor" @selected(old('role', $user->role) === 'editor')>Pengelola</option><option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option></select></div><div class="field"><label for="is_active">Status akun</label><select id="is_active" name="is_active" required><option value="1" @selected((string) old('is_active', (int) $user->is_active) === '1')>Aktif</option><option value="0" @selected((string) old('is_active', (int) $user->is_active) === '0')>Nonaktif</option></select></div></div>
    @unless($user->exists)
        <hr><div class="field"><label for="password">Password awal</label><input type="password" id="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"><small>Minimal 8 karakter, berisi huruf dan angka. Pengguna wajib menggantinya saat login pertama.</small></div>
        <div class="field"><label for="password_confirmation">Konfirmasi password awal</label><input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password"></div>
    @endunless
    <div class="heading-actions user-form-actions"><a class="button secondary" href="{{ route('users.index') }}">Batal</a><button class="button primary" type="submit">{{ $user->exists ? 'Simpan perubahan' : 'Simpan pengguna' }}</button></div>
</form></section>
@if($user->exists && ! $user->is(auth()->user()))
<section class="panel account-panel"><div class="section-heading"><h2>Reset password</h2></div><p class="account-description">Sesi login lama akan berakhir. Pengguna wajib mengganti password sementara saat masuk kembali.</p>
    <form action="{{ route('users.password', $user) }}" method="post">@csrf @method('PUT')
        <div class="field"><label for="reset_password">Password sementara baru</label><input type="password" id="reset_password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"></div>
        <div class="field"><label for="reset_password_confirmation">Konfirmasi password sementara</label><input type="password" id="reset_password_confirmation" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password"></div>
        <button class="button secondary" type="submit">Reset password</button>
    </form>
</section>
@elseif($user->exists)<a class="text-link" href="{{ route('account.password') }}">Ganti password akun Anda</a>@endif
@endsection
