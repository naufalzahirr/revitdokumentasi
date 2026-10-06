@extends('layouts.app')
@section('title', 'Semua nota')
@section('breadcrumb', 'Semua nota')
@section('content')
<div class="page-heading"><div><div class="eyebrow">ARSIP KEGIATAN</div><h1>Dokumentasi revitalisasi<span class="heading-dot">.</span></h1><p>Satu nota, beberapa gambar. Simpan yang sudah ada dan lengkapi kapan saja.</p></div><a class="button primary" href="{{ route('documents.create') }}"><x-icon name="plus"/>Tambah nota</a></div>
<div class="stats-grid">
    <div class="stat-card"><div><span class="stat-label">Total nota</span><strong>{{ $stats['documents'] }}</strong><span class="stat-hint">Nota tersimpan</span></div><span class="stat-icon green"><x-icon name="file" size="24"/></span></div>
    <div class="stat-card"><div><span class="stat-label">Foto dokumentasi</span><strong>{{ $stats['photos'] }}</strong><span class="stat-hint">Momen terdokumentasi</span></div><span class="stat-icon blue"><x-icon name="image" size="24"/></span></div>
    <div class="stat-card"><div><span class="stat-label">Kategori pembangunan</span><strong>{{ $stats['categories'] }}</strong><span class="stat-hint">Jenis kegiatan revitalisasi</span></div><span class="stat-icon amber"><x-icon name="folder" size="24"/></span></div>
</div>
<section class="archive-section">
    <div class="section-heading"><h2>Semua nota <span class="count-badge">{{ $documents->total() }}</span></h2><span class="muted small">Terbaru lebih dahulu</span></div>
    <form action="{{ route('documents.index') }}" method="get" class="filter-bar">
        <div class="search-field"><x-icon name="search"/><input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kategori atau nomor nota…" aria-label="Cari nota" maxlength="100"></div>
        <select name="category" aria-label="Filter kategori"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach</select>
        <button class="button secondary" type="submit">Cari</button>
        @if(request()->filled('q') || request()->filled('category'))<a href="{{ route('documents.index') }}" class="text-link">Reset</a>@endif
    </form>
    @if($documents->isEmpty())
        <div class="empty-state"><div class="empty-art"><span class="empty-back"></span><span class="empty-front"><x-icon name="image" size="44"/></span><span class="empty-plus">+</span></div>
            @if(request()->filled('q') || request()->filled('category'))<h3>Nota belum ditemukan</h3><p>Coba kata kunci lain atau tampilkan semua kategori.</p><a class="button secondary" href="{{ route('documents.index') }}">Tampilkan semua nota</a>
            @else<h3>Tambahkan nota pertama Anda</h3><p>Simpan informasi nota terlebih dahulu.<br>Tambahkan gambar kegiatan sekarang atau nanti.</p><a class="button primary" href="{{ route('documents.create') }}"><x-icon name="plus"/>Buat nota pertama</a><span class="empty-help">Isi nota → Unggah foto → Cetak nota</span>@endif
        </div>
    @else
        <div class="document-grid">
        @foreach($documents as $document)
            <article class="document-card"><a href="{{ route('documents.show', $document) }}" class="card-image" aria-label="Lihat {{ $document->category }}">@if($document->coverPhoto)<img src="{{ route('photos.show', $document->coverPhoto) }}" alt="{{ $document->coverPhoto->caption }}" loading="lazy">@else<span class="cover-empty"><x-icon name="image" size="32"/><span>Gambar bisa ditambahkan nanti</span></span>@endif<span class="photo-badge"><x-icon name="image" size="14"/>{{ $document->photos_count }} foto</span></a><div class="card-body"><span class="category-tag">{{ $document->category }}</span><h3><a href="{{ route('documents.show', $document) }}">{{ $document->receipt_number }}</a></h3><div class="card-date"><x-icon name="calendar" size="15"/>{{ $document->receipt_date->translatedFormat('d F Y') }}</div><div class="card-actions"><a class="text-link" href="{{ route('documents.show', $document) }}">Lihat nota <x-icon name="arrow-right" size="15"/></a><a class="icon-button" href="{{ route('documents.print', $document) }}" aria-label="Cetak nota {{ $document->receipt_number }}" title="Cetak nota"><x-icon name="print" size="18"/></a></div></div></article>
        @endforeach
        </div>
        @if($documents->hasPages())<nav class="pagination" aria-label="Halaman nota"><span class="muted small">{{ $documents->firstItem() }}–{{ $documents->lastItem() }} dari {{ $documents->total() }} nota</span><div>@if($documents->onFirstPage())<span class="button secondary disabled">Sebelumnya</span>@else<a class="button secondary" href="{{ $documents->previousPageUrl() }}">Sebelumnya</a>@endif<span class="page-number">{{ $documents->currentPage() }} / {{ $documents->lastPage() }}</span>@if($documents->hasMorePages())<a class="button secondary" href="{{ $documents->nextPageUrl() }}">Berikutnya</a>@else<span class="button secondary disabled">Berikutnya</span>@endif</div></nav>@endif
    @endif
</section>
<div class="tip-strip"><span class="tip-icon"><x-icon name="print"/></span><p><strong>Siap untuk kebutuhan laporan.</strong> Setiap nota dapat dicetak dalam ukuran A4 atau disimpan sebagai PDF.</p><span class="a4-badge">A4</span></div>
@endsection
