<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Berkas nota — {{ $document->receipt_number }}</title><link rel="stylesheet" href="{{ asset('css/app.css') }}"><link rel="stylesheet" href="{{ asset('css/print.css') }}"><link rel="stylesheet" href="{{ asset('css/voucher.css') }}"><script src="{{ asset('js/print.js') }}" defer></script></head>
@php
    $shared = $shared ?? false;
    $routeDocument = $shared ? $document->share_token : $document;
    $pages = $document->photos->isEmpty() ? collect([collect()]) : $document->photos->chunk(2);
    $totalPages = 2 + $pages->count();
@endphp
<body class="print-preview">
<header class="print-toolbar"><a class="back-link" href="{{ route($shared ? 'shared.show' : 'documents.show', $routeDocument) }}"><x-icon name="arrow" size="18"/>Kembali ke nota</a><div><span class="print-format">Satu berkas · A4 · {{ $totalPages }} halaman</span><button class="button primary" id="print-button"><x-icon name="print" size="18"/>Cetak / Simpan PDF</button></div></header>
<p class="print-instruction" id="print-instruction" role="status">Urutan: bukti pengeluaran, foto nota, dokumentasi. Pilih kertas A4, skala 100%, dan nonaktifkan header/footer browser. Untuk PDF, pilih “Save as PDF”.</p>
<main class="print-pages">
    <section class="sheet voucher-sheet" data-section="voucher">
        @include('documents.partials.voucher-content')
        @include('documents.partials.report-footer', ['pageNumber' => 1])
    </section>
    <section class="sheet receipt-sheet" data-section="receipt">
        @include('documents.partials.report-header', ['reportTitle' => 'Foto Nota'])
        @if($document->receipt_image_path)
            <img class="receipt-report-image" src="{{ route($shared ? 'shared.receipt-image' : 'documents.receipt-image', $routeDocument) }}" alt="Gambar nota {{ $document->receipt_number }}">
        @else
            <p class="report-empty">Belum ada gambar nota yang diunggah.</p>
        @endif
        @include('documents.partials.report-footer', ['pageNumber' => 2])
    </section>
@foreach($pages as $pagePhotos)
    <section class="sheet documentation-sheet" data-section="documentation">
        @include('documents.partials.report-header')
        <div class="report-photos">@forelse($pagePhotos as $photo)<figure class="report-photo"><img src="{{ ($shared ? route('shared.photo', ['document' => $routeDocument, 'photo' => $photo]) : route('photos.show', $photo)) }}" alt="{{ $photo->caption }}"><figcaption><span>Gambar {{ $loop->parent->index * 2 + $loop->iteration }}</span><p>{{ $photo->caption ?: 'Keterangan belum diisi.' }}</p></figcaption></figure>@empty<p class="report-empty">Belum ada gambar dalam nota ini.</p>@endforelse</div>
        @include('documents.partials.report-footer', ['pageNumber' => $loop->iteration + 2])
    </section>
@endforeach
</main>
</body>
</html>
