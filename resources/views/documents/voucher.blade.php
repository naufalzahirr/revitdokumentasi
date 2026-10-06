<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Bukti Pengeluaran Dana — {{ $document->voucher_number ?: $document->receipt_number }}</title><link rel="stylesheet" href="{{ asset('css/app.css') }}"><link rel="stylesheet" href="{{ asset('css/print.css') }}"><link rel="stylesheet" href="{{ asset('css/voucher.css') }}"><script src="{{ asset('js/print.js') }}" defer></script></head>
@php
    $shared = $shared ?? false;
    $routeDocument = $shared ? $document->share_token : $document;
    $blank = '................................';
    $amount = $document->amount === null ? $blank : \App\Support\Rupiah::format($document->amount);
@endphp
<body class="print-preview">
<header class="print-toolbar"><a class="back-link" href="{{ route($shared ? 'shared.show' : 'documents.show', $routeDocument) }}"><x-icon name="arrow" size="18"/>Kembali ke nota</a><div><span class="print-format">Bukti Pengeluaran Dana · A4</span><button class="button primary" id="print-button"><x-icon name="print" size="18"/>Cetak / Simpan PDF</button></div></header>
<p class="print-instruction" id="print-instruction" role="status">Pilih kertas A4, skala 100%, dan nonaktifkan header/footer browser. Untuk PDF, pilih “Save as PDF”.</p>
<main class="print-pages"><section class="sheet voucher-sheet">
    <h1 class="voucher-title">BUKTI PENGELUARAN DANA</h1>
    <div class="voucher-box">
        <div class="voucher-number">Nomor : {{ $document->voucher_number ?: $document->receipt_number }}</div>
        <div class="voucher-body">
            <div class="voucher-row"><span>Sudah Terima Dari</span><span>:</span><p>{{ $document->received_from ?: $blank }}</p></div>
            <div class="voucher-row"><span>Uang Sebesar</span><span>:</span><p>{{ $amount }} @if($document->amount !== null)<span class="voucher-words">({{ \App\Support\Rupiah::words($document->amount) }})</span>@endif</p></div>
            <div class="voucher-row"><span>Untuk Keperluan</span><span>:</span><p>{{ $document->purpose ?: $blank }}</p></div>
            <strong class="voucher-amount">{{ $amount }}</strong>
        </div>
        <div class="voucher-signatures">
            <div class="voucher-signature"><div>Setuju Dibayar :<br>{{ $document->approver_title ?: 'Jabatan: '.$blank }}</div><div class="signature-space"></div><div>{{ $document->approver_name ?: $blank }}<br>NIP {{ $document->approver_nip ?: $blank }}</div></div>
            <div class="voucher-signature"><div>Lunas Dibayar :<br>{{ $document->payment_date?->translatedFormat('d F Y') ?: $blank }}<br>Bendahara</div><div class="signature-space"></div><div>{{ $document->treasurer_name ?: $blank }}<br>NIP {{ $document->treasurer_nip ?: $blank }}</div></div>
            <div class="voucher-signature"><div>{{ $document->payment_place ?: $blank }},<br>Penerima Pembayaran</div><div class="signature-space"></div><div>{{ $document->recipient_name ?: $blank }}<div class="recipient-nip-line">NIP <span>{{ $document->recipient_nip }}</span></div></div></div>
        </div>
    </div>
</section></main>
</body>
</html>
