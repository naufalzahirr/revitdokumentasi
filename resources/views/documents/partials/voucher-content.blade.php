@php
    $blank = '................................';
    $amount = $document->amount === null ? $blank : \App\Support\Rupiah::format($document->amount);
@endphp
    <h1 class="voucher-title">BUKTI PENGELUARAN DANA</h1>
    <div class="voucher-box">
        <div class="voucher-number">Nomor : <span class="voucher-manual-number" aria-hidden="true"></span>/REV.SMK</div>
        <div class="voucher-body">
            <div class="voucher-row"><span>Sudah Terima Dari</span><span>:</span><p>{{ $document->received_from ?: $blank }}</p></div>
            <div class="voucher-row"><span>Uang Sebesar</span><span>:</span><p>{{ $amount }} @if($document->amount !== null)<span class="voucher-words">({{ \App\Support\Rupiah::words($document->amount) }})</span>@endif</p></div>
            <div class="voucher-row"><span>Untuk Keperluan</span><span>:</span><p>{{ $document->purpose ?: $blank }}</p></div>
            <strong class="voucher-amount">{{ $amount }}</strong>
        </div>
        <div class="voucher-signatures">
            <div class="voucher-signature"><div>Setuju Dibayar :<br>{{ $document->approver_title ?: 'Jabatan: '.$blank }}</div><div class="signature-space"></div><div>{{ $document->approver_name ?: $blank }}<br>NIP {{ $document->approver_nip ?: $blank }}</div></div>
            <div class="voucher-signature"><div><span class="voucher-payment-line">Lunas Dibayar : <span class="voucher-manual-day" aria-hidden="true"></span>{{ $document->payment_date?->translatedFormat('F Y') ?: $blank }}</span><br>Bendahara</div><div class="signature-space"></div><div>{{ $document->treasurer_name ?: $blank }}<br>NIP {{ $document->treasurer_nip ?: $blank }}</div></div>
            <div class="voucher-signature"><div>{{ $document->payment_place ?: $blank }},<br>Penerima Pembayaran</div><div class="signature-space"></div><div>{{ $document->recipient_name ?: $blank }}<div class="recipient-nip-line">@if(filled($document->recipient_nip))NIP <span>{{ $document->recipient_nip }}</span>@endif</div></div></div>
        </div>
    </div>
