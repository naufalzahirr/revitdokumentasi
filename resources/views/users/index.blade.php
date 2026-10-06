@extends('layouts.app')
@section('title', 'Pengguna')
@section('breadcrumb', 'Pengguna')
@section('content')
<div class="page-heading"><div><div class="eyebrow">PENGELOLAAN AKUN</div><h1>Pengguna<span class="heading-dot">.</span></h1><p>Atur akun dan akses pengelola dokumentasi.</p></div><a class="button primary" href="{{ route('users.create') }}"><x-icon name="plus"/>Tambah pengguna</a></div>
<section class="panel"><div class="section-heading"><h2>Daftar pengguna</h2><span class="count-badge">{{ $users->total() }} akun</span></div><div class="user-list">
@foreach($users as $user)
    <div class="user-row"><div><strong>{{ $user->name }}</strong><p class="muted small">{{ $user->username }}</p></div><div class="user-badges"><span class="count-badge">{{ $user->isAdmin() ? 'Admin' : 'Pengelola' }}</span><span class="user-status {{ $user->is_active ? 'active' : 'inactive' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>@if($user->must_change_password)<span class="muted small">Wajib ganti password</span>@endif</div><a class="button secondary" href="{{ route('users.edit', $user) }}">Kelola</a></div>
@endforeach
</div>{{ $users->links('partials.pagination') }}</section>
@endsection
