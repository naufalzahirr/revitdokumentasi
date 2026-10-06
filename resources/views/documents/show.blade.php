@extends('layouts.app')
@section('title', $document->receipt_number)
@section('breadcrumb', 'Detail nota')
@section('content')
<a class="back-link" href="{{ route('documents.index') }}"><x-icon name="arrow" size="17"/>Kembali ke arsip</a>
<div class="page-heading"><div><div class="eyebrow">DETAIL NOTA</div><h1>Nota {{ $document->receipt_number }}<span class="heading-dot">.</span></h1><p>Satu nota untuk semua gambar kegiatan. Tambahkan gambar berikutnya kapan saja.</p></div><div class="heading-actions"><a class="button secondary" href="{{ route('documents.edit', $document) }}"><x-icon name="edit" size="17"/>Edit nota</a><a class="button primary" href="{{ route('documents.print', $document) }}"><x-icon name="print" size="18"/>Cetak berkas nota</a></div></div>
<div class="detail-meta"><div><span>Kategori pembangunan</span><strong>{{ $document->category }}</strong></div><div><span>Tanggal nota</span><strong>{{ $document->receipt_date->translatedFormat('d F Y') }}</strong></div><div><span>Nomor nota</span><strong>{{ $document->receipt_number }}</strong></div><div><span>Foto dokumentasi</span><strong>{{ $document->photos->count() }} foto</strong></div></div>
@include('documents.partials.print-options')
@include('documents.partials.sharing')
@include('documents.partials.media')
<div class="document-bottom"><span class="muted small">Ditambahkan {{ $document->created_at->translatedFormat('d F Y, H:i') }} WIB</span><form action="{{ route('documents.destroy', $document) }}" method="post" data-confirm="Hapus nota ini beserta seluruh gambarnya? Tindakan ini tidak dapat dibatalkan.">@csrf @method('DELETE')<button type="submit" class="button danger-ghost"><x-icon name="trash" size="17"/>Hapus nota</button></form></div>
@endsection
