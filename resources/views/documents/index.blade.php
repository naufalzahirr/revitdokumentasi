@extends('layouts.app')
@section('title', 'Semua nota')
@section('breadcrumb', 'Semua nota')
@section('content')
<div class="page-heading archive-heading"><div><div class="eyebrow">ARSIP KEGIATAN</div><h1>Dokumentasi revitalisasi<span class="heading-dot">.</span></h1><p>Pantau kelengkapan setiap nota. Simpan yang sudah ada dan lengkapi kapan saja.</p></div><a class="button primary" href="{{ route('documents.create') }}"><x-icon name="plus"/>Tambah nota</a></div>
<div class="archive-counts" aria-label="Ringkasan nota">
    <span>Total nota <strong>{{ $stats['documents'] }}</strong></span>
    <a href="{{ route('documents.index', ['status' => 'complete']) }}">Lengkap <strong>{{ $stats['complete'] }}</strong></a>
    <a href="{{ route('documents.index', ['status' => 'incomplete']) }}">Perlu dilengkapi <strong>{{ $stats['incomplete'] }}</strong></a>
</div>
<section class="archive-progress compact-progress" aria-label="Progres seluruh nota">
    <div class="progress-heading"><div><h2>Progres seluruh nota</h2><p>{{ $stats['complete'] }} dari {{ $stats['documents'] }} nota lengkap</p></div><strong>{{ $stats['percentage'] }}%</strong></div>
    <progress class="archive-progress-bar" max="100" value="{{ $stats['percentage'] }}" aria-label="Persentase nota lengkap">{{ $stats['percentage'] }}%</progress>
    <div class="progress-totals"><span>Data lengkap: <strong>{{ $stats['data_complete'] }}/{{ $stats['documents'] }} nota</strong></span><span>Foto dan keterangan lengkap: <strong>{{ $stats['photos_complete'] }}/{{ $stats['documents'] }} nota</strong></span><span>{{ $stats['photos'] }} foto · {{ $stats['categories'] }} kategori</span></div>
    <details class="progress-criteria"><summary>Patokan kelengkapan</summary><p>Data: kategori, tanggal, nomor nota, gambar nota, nominal lebih dari nol, keperluan, dan nama penerima pembayaran. NIP penerima opsional. Dokumentasi: minimal satu item, setiap item punya minimal satu foto, dan setiap foto memiliki keterangan. Status dihitung dari isian yang tersimpan.</p></details>
</section>
<section class="archive-section archive-table-section">
    <div class="section-heading"><h2>Semua nota <span class="count-badge">{{ $documents->total() }}</span></h2><span class="muted small">ID terkecil lebih dahulu · 50 nota per halaman</span></div>
    <form action="{{ route('documents.index') }}" method="get" class="filter-bar progress-filter">
        <div class="search-field"><x-icon name="search"/><input type="search" name="q" value="{{ request('q') }}" placeholder="Cari ID, kategori, atau keperluan…" aria-label="Cari nota" maxlength="100"></div>
        <select name="category" aria-label="Filter kategori"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach</select>
        <select name="status" aria-label="Filter kelengkapan"><option value="">Semua status</option>@foreach(['complete' => 'Lengkap', 'incomplete' => 'Perlu dilengkapi', 'data_incomplete' => 'Data belum lengkap', 'photos_incomplete' => 'Foto/keterangan belum lengkap'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
        <button class="button secondary" type="submit">Cari</button>
        @if(request()->filled('q') || request()->filled('category') || request()->filled('status'))<a href="{{ route('documents.index') }}" class="text-link">Reset</a>@endif
    </form>
    @if($documents->isEmpty())
        <div class="empty-state"><div class="empty-art"><span class="empty-back"></span><span class="empty-front"><x-icon name="image" size="44"/></span><span class="empty-plus">+</span></div>
            @if(request()->filled('q') || request()->filled('category') || request()->filled('status'))<h3>Nota belum ditemukan</h3><p>Coba kata kunci atau filter lain.</p><a class="button secondary" href="{{ route('documents.index') }}">Tampilkan semua nota</a>
            @else<h3>Tambahkan nota pertama Anda</h3><p>Simpan informasi nota terlebih dahulu.<br>Tambahkan gambar kegiatan sekarang atau nanti.</p><a class="button primary" href="{{ route('documents.create') }}"><x-icon name="plus"/>Buat nota pertama</a><span class="empty-help">Isi nota → Unggah foto → Cetak nota</span>@endif
        </div>
    @else
        <div class="nota-table-scroll" role="region" aria-label="Tabel semua nota, geser untuk melihat seluruh kolom" tabindex="0">
            <table class="nota-table">
                <caption class="visually-hidden">Semua nota, diurutkan berdasarkan ID terkecil</caption>
                <thead><tr><th scope="col">ID</th><th scope="col">Tanggal nota</th><th scope="col">Kategori / keperluan</th><th scope="col" class="nota-amount">Nominal</th><th scope="col">Kelengkapan</th><th scope="col">Aksi</th></tr></thead>
                <tbody>
                @foreach($documents as $document)
                    <tr>
                        <th scope="row" class="nota-id"><a href="{{ route('documents.show', $document) }}" aria-label="Lihat nota ID {{ $document->id }}">{{ $document->id }}</a></th>
                        <td class="nota-date">{{ $document->receipt_date?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td class="nota-description"><strong>{{ $document->category ?: 'Kategori belum diisi' }}</strong><span class="nota-purpose" title="{{ $document->purpose }}">{{ $document->purpose ?: 'Keperluan belum diisi' }}</span></td>
                        <td class="nota-amount">{{ $document->amount !== null ? \App\Support\Rupiah::format($document->amount) : '—' }}</td>
                        <td class="nota-completion">@include('documents.partials.progress')</td>
                        <td><div class="nota-actions"><a href="{{ route('documents.show', $document) }}">Lihat</a><a href="{{ route('documents.edit', $document) }}">{{ $document->progress()['complete'] ? 'Edit' : 'Lengkapi' }}</a><a href="{{ route('documents.print', $document) }}" aria-label="Cetak nota ID {{ $document->id }}"><x-icon name="print" size="14"/> Cetak</a></div></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @unless($documents->hasPages())<p class="nota-range muted small">Menampilkan {{ $documents->firstItem() }}–{{ $documents->lastItem() }} dari {{ $documents->total() }} nota</p>@endunless
        @if($documents->hasPages())<nav class="pagination" aria-label="Halaman nota"><span class="muted small">{{ $documents->firstItem() }}–{{ $documents->lastItem() }} dari {{ $documents->total() }} nota</span><div>@if($documents->onFirstPage())<span class="button secondary disabled">Sebelumnya</span>@else<a class="button secondary" href="{{ $documents->previousPageUrl() }}">Sebelumnya</a>@endif<span class="page-number">{{ $documents->currentPage() }} / {{ $documents->lastPage() }}</span>@if($documents->hasMorePages())<a class="button secondary" href="{{ $documents->nextPageUrl() }}">Berikutnya</a>@else<span class="button secondary disabled">Berikutnya</span>@endif</div></nav>@endif
    @endif
</section>
<div class="tip-strip"><span class="tip-icon"><x-icon name="print"/></span><p><strong>Siap untuk kebutuhan laporan.</strong> Setiap nota dapat dicetak dalam ukuran A4 atau disimpan sebagai PDF.</p><span class="a4-badge">A4</span></div>
@endsection
