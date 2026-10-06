@extends('layouts.app')
@section('title', 'Kategori')
@section('breadcrumb', 'Kategori')
@section('content')
<div class="page-heading"><div><div class="eyebrow">JENIS PEMBANGUNAN</div><h1>Kategori<span class="heading-dot">.</span></h1><p>Kelola pilihan kategori yang digunakan saat menambah atau mengedit nota.</p></div></div>
<div class="category-layout">
    <section class="panel"><div class="section-heading"><h2>Tambah kategori</h2></div>
        <form action="{{ route('categories.store') }}" method="post">@csrf
            <div class="field"><label for="name">Nama kategori <span>*</span></label><input id="name" name="name" value="{{ old('name') }}" required maxlength="100" placeholder="Contoh: Pembangunan laboratorium"></div>
            <button class="button primary" type="submit">Simpan kategori</button>
        </form>
    </section>
    <section class="panel"><div class="section-heading"><h2>Daftar kategori</h2><span class="count-badge">{{ $categories->count() }} kategori</span></div>
        <div class="category-list">@forelse($categories as $category)
            <div class="category-row"><div><strong>{{ $category->name }}</strong><small>{{ $category->documents_count }} nota</small></div><div class="category-actions">
                <a class="button secondary" href="{{ route('categories.edit', $category) }}">Edit</a>
                @if($category->documents_count === 0)
                    <form action="{{ route('categories.destroy', $category) }}" method="post" data-confirm="Hapus kategori ini?">@csrf @method('DELETE')<button class="button danger-ghost" type="submit">Hapus</button></form>
                @else
                    <span class="muted small">Sedang digunakan</span>
                @endif
            </div></div>
        @empty<p class="muted small">Belum ada kategori. Tambahkan kategori terlebih dahulu.</p>@endforelse</div>
    </section>
</div>
@endsection
