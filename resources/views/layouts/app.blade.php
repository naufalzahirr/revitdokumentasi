<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dokumen') · Revita</title>
    <meta name="theme-color" content="#175b4b">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
<aside class="sidebar">
    <a href="{{ route('home') }}" class="brand"><span class="brand-mark">r<span>.</span></span><span>revita<span class="brand-caption">DOKUMENTASI REVITALISASI</span></span></a>
    <div class="workspace-label">RUANG KERJA</div>
    <nav aria-label="Menu utama">
        @unless(auth()->user()->must_change_password)
        <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('home', 'documents.index', 'documents.show', 'documents.edit') ? 'active' : '' }}"><x-icon name="grid"/> <span>Semua nota</span></a>
        <a href="{{ route('documents.create') }}" class="nav-link {{ request()->routeIs('documents.create') ? 'active' : '' }}"><x-icon name="plus"/> <span>Tambah nota</span></a>
        <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"><x-icon name="folder"/> <span>Kategori</span></a>
        @if(auth()->user()->isAdmin())<a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"><x-icon name="users"/> <span>Pengguna</span></a>@endif
        @endunless
        <a href="{{ route('account.password') }}" class="nav-link {{ request()->routeIs('account.*') ? 'active' : '' }}"><x-icon name="lock"/> <span>Ganti password</span></a>
    </nav>
    <div class="sidebar-note"><span class="note-icon"><x-icon name="print" size="22"/></span><strong>Dari foto, jadi laporan.</strong><p>Simpan dokumentasi kegiatan dan cetak rapi dalam format A4.</p><span class="small-label">SEDERHANA. TERORGANISIR.</span></div>
    <div class="sidebar-footer"><span class="status-dot"></span> Arsip dokumentasi <span class="version">v1.0</span></div>
</aside>
<div class="app-shell">
    <header class="topbar"><div><span class="breadcrumb">Ruang kerja</span><span class="breadcrumb-divider">/</span><strong>@yield('breadcrumb', 'Dokumen')</strong></div><div class="topbar-right"><span class="today">{{ now()->translatedFormat('d F Y') }}</span><a href="{{ route('account.password') }}" class="account-label" title="Ganti password">{{ auth()->user()->username }}<span class="account-role">{{ auth()->user()->isAdmin() ? 'Admin' : 'Pengelola' }}</span></a><form action="{{ route('logout') }}" method="post">@csrf<button class="logout-button" type="submit"><x-icon name="logout" size="17"/><span>Keluar</span></button></form></div></header>
    <main class="main-content">
        @if(session('success'))<div class="alert success" role="status"><x-icon name="check"/><span>{{ session('success') }}</span></div>@endif
        @if($errors->any())
            <div class="alert error" role="alert"><strong>Periksa kembali data yang diisi.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@if(request()->routeIs('documents.create', 'documents.edit'))<p>Jika sebelumnya memilih foto baru, pilih kembali fotonya sebelum menyimpan.</p>@endif</div>
        @endif
        @yield('content')
        <footer class="page-footer"><span>Revita — Dokumentasi revitalisasi</span><span>Setiap perubahan, terdokumentasi.</span></footer>
    </main>
</div>
</body>
</html>
