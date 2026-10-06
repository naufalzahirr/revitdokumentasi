@extends('layouts.app')
@section('title', 'Ganti password')
@section('breadcrumb', 'Akun')
@section('content')
<div class="page-heading"><div><div class="eyebrow">AKUN ANDA</div><h1>Ganti password<span class="heading-dot">.</span></h1><p>{{ auth()->user()->must_change_password ? 'Ganti password awal sebelum menggunakan aplikasi.' : 'Perbarui password akun Anda.' }}</p></div></div>
<section class="panel account-panel"><p class="account-description">Username: <strong>{{ auth()->user()->username }}</strong>. Password baru minimal 8 karakter, berisi huruf dan angka, serta berbeda dari password saat ini.</p>
    <form action="{{ route('account.password.update') }}" method="post">@csrf @method('PUT')
        <div class="field"><label for="current_password">Password saat ini</label><input type="password" id="current_password" name="current_password" required maxlength="255" autocomplete="current-password" autofocus></div>
        <div class="field"><label for="password">Password baru</label><input type="password" id="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"></div>
        <div class="field"><label for="password_confirmation">Konfirmasi password baru</label><input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password"></div>
        <button class="button primary" type="submit">Simpan password</button>
    </form>
</section>
@endsection
