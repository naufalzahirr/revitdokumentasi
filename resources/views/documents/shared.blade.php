<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><meta name="referrer" content="no-referrer"><title>Nota {{ $document->receipt_number }} · Revita</title><link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body class="shared-document">
<header class="shared-header"><div class="brand"><span class="brand-mark">r<span>.</span></span><span>revita</span></div><span class="category-tag">Nota dibagikan</span></header>
<main class="shared-content">
    <div class="page-heading"><div><div class="eyebrow">DOKUMENTASI REVITALISASI</div><h1>Nota {{ $document->receipt_number }}<span class="heading-dot">.</span></h1><p>{{ $document->category }}</p></div><a class="button primary" href="{{ route('shared.print', $document->share_token) }}"><x-icon name="print" size="18"/>Cetak berkas nota</a></div>
    <div class="detail-meta"><div><span>Kategori pembangunan</span><strong>{{ $document->category }}</strong></div><div><span>Tanggal nota</span><strong>{{ $document->receipt_date->translatedFormat('d F Y') }}</strong></div><div><span>Nomor nota</span><strong>{{ $document->receipt_number }}</strong></div><div><span>Dokumentasi</span><strong>{{ $document->items->count() }} item · {{ $document->photos->count() }} foto</strong></div></div>
    @include('documents.partials.print-options', ['shared' => true])
    @include('documents.partials.media', ['shared' => true])
    <footer class="page-footer"><span>Revita — Dokumentasi revitalisasi</span><span>Halaman berbagi</span></footer>
</main>
</body>
</html>
