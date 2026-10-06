<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Dokumentasi — {{ $document->receipt_number }}</title><link rel="stylesheet" href="{{ asset('css/app.css') }}"><link rel="stylesheet" href="{{ asset('css/print.css') }}"><script src="{{ asset('js/print.js') }}" defer></script></head>
@php
    $shared = $shared ?? false;
    $routeDocument = $shared ? $document->share_token : $document;
    $pages = $document->photos->isEmpty() ? collect([collect()]) : $document->photos->chunk(2);
    $totalPages = $pages->count() + ($document->receipt_image_path ? 1 : 0);
@endphp
<body class="print-preview">
<header class="print-toolbar"><a class="back-link" href="{{ route($shared ? 'shared.show' : 'documents.show', $routeDocument) }}"><x-icon name="arrow" size="18"/>Kembali ke nota</a><div><span class="print-format">A4 · {{ $totalPages }} halaman</span><button class="button primary" id="print-button"><x-icon name="print" size="18"/>Cetak / Simpan PDF</button></div></header>
<p class="print-instruction" id="print-instruction" role="status">Pilih kertas A4, skala 100%, dan nonaktifkan header/footer browser. Untuk PDF, pilih “Save as PDF”.</p>
<main class="print-pages">
@foreach($pages as $pagePhotos)
    <section class="sheet">
        @include('documents.partials.report-header')
        <div class="report-photos">@forelse($pagePhotos as $photo)<figure class="report-photo"><img src="{{ ($shared ? route('shared.photo', ['document' => $routeDocument, 'photo' => $photo]) : route('photos.show', $photo)) }}" alt="{{ $photo->caption }}"><figcaption><span>Gambar {{ $loop->parent->index * 2 + $loop->iteration }}</span><p>{{ $photo->caption ?: 'Keterangan belum diisi.' }}</p></figcaption></figure>@empty<p class="report-empty">Belum ada gambar dalam nota ini.</p>@endforelse</div>
        @include('documents.partials.report-footer', ['pageNumber' => $loop->iteration])
    </section>
@endforeach
@if($document->receipt_image_path)
    <section class="sheet receipt-sheet">
        @include('documents.partials.report-header')
        <h2 class="receipt-report-title">Lampiran gambar nota</h2>
        <img class="receipt-report-image" src="{{ route($shared ? 'shared.receipt-image' : 'documents.receipt-image', $routeDocument) }}" alt="Gambar nota {{ $document->receipt_number }}">
        @include('documents.partials.report-footer', ['pageNumber' => $totalPages])
    </section>
@endif
</main>
</body>
</html>
