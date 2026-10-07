<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Berkas nota — {{ $document->receipt_number }}</title><link rel="stylesheet" href="{{ \App\Support\Asset::url('css/app.css') }}"><link rel="stylesheet" href="{{ \App\Support\Asset::url('css/print.css') }}"><link rel="stylesheet" href="{{ \App\Support\Asset::url('css/voucher.css') }}"><script src="{{ \App\Support\Asset::url('js/print.js') }}" defer></script></head>
@php
    $shared = $shared ?? false;
    $routeDocument = $shared ? $document->share_token : $document;
    $entries = $document->items->flatMap(fn ($item) => $item->photos->isEmpty()
        ? collect([['item' => $item, 'photo' => null]])
        : $item->photos->map(fn ($photo) => ['item' => $item, 'photo' => $photo]));
    $photoNumbers = $document->items->flatMap->photos->values()->mapWithKeys(fn ($photo, $index) => [$photo->id => $index + 1]);
    $pages = $entries->isEmpty() ? collect([collect()]) : $entries->chunk(2);
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
@foreach($pages as $pageEntries)
    <section class="sheet documentation-sheet" data-section="documentation">
        @include('documents.partials.report-header')
        <div class="report-photos">@forelse($pageEntries as $entry)
            @php($photo = $entry['photo'])
            @if($photo)
                <figure class="report-photo"><h2 class="report-item-name">{{ $entry['item']->name }}</h2><img src="{{ ($shared ? route('shared.photo', ['document' => $routeDocument, 'photo' => $photo]) : route('photos.show', $photo)) }}" alt="{{ $photo->caption ?: $entry['item']->name }}"><figcaption><span>Gambar {{ $photoNumbers[$photo->id] }}</span><p>{{ $photo->caption ?: 'Keterangan belum diisi.' }}</p></figcaption></figure>
            @else
                <article class="report-item-empty"><h2 class="report-item-name">{{ $entry['item']->name }}</h2><p>Foto item belum ditambahkan.</p></article>
            @endif
        @empty<p class="report-empty">Belum ada gambar dalam nota ini.</p>@endforelse</div>
        @include('documents.partials.report-footer', ['pageNumber' => $loop->iteration + 2])
    </section>
@endforeach
</main>
</body>
</html>
