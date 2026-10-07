<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Masuk · Revita</title><link rel="stylesheet" href="{{ \App\Support\Asset::url('css/app.css') }}"><link rel="stylesheet" href="{{ \App\Support\Asset::url('css/auth.css') }}"></head>
<body class="login-page"><main class="login-card">
    <div class="brand"><span class="brand-mark">r<span>.</span></span><span>revita<span class="brand-caption">DOKUMENTASI REVITALISASI</span></span></div>
    <div class="login-heading"><div class="eyebrow">RUANG KERJA</div><h1>Selamat datang.</h1><p>Masuk untuk mengelola nota, foto, dan dokumentasi revitalisasi.</p></div>
    @if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form action="{{ route('login.store') }}" method="post">@csrf
        <div class="field"><label for="username">Username</label><input id="username" name="username" value="{{ old('username') }}" required maxlength="50" autocomplete="username" autocapitalize="none" spellcheck="false" autofocus></div>
        <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required maxlength="255" autocomplete="current-password"></div>
        <button class="button primary login-submit" type="submit"><x-icon name="lock" size="17"/>Masuk</button>
    </form>
    <p class="login-help">Belum punya akun atau lupa password? Hubungi admin.</p>
</main><footer class="login-footer">Revita — Dokumentasi revitalisasi</footer></body>
</html>
