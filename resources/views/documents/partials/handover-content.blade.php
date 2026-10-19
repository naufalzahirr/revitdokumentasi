<header class="handover-letterhead">
    <img src="{{ \App\Support\Asset::url('images/kepri.png') }}" alt="Lambang Provinsi Kepulauan Riau">
    <div>
        <div>{{ config('handover.government') }}</div>
        <div>{{ config('handover.department') }}</div>
        <strong>{{ config('handover.school') }}</strong>
        <p>{{ config('handover.address') }}</p>
        <p>{{ config('handover.contact') }}</p>
    </div>
    <img src="{{ \App\Support\Asset::url('images/smkn4.gif') }}" alt="Logo SMK Negeri 4 Tanjungpinang">
</header>
<h1 class="handover-title">BERITA ACARA SERAH TERIMA</h1>
<p class="handover-number">Nomor : <span class="handover-manual-number" aria-hidden="true"></span>{{ config('handover.number_suffix') }}{{ $document->receipt_date?->year }}</p>
<div class="handover-body">
    <p>Pada hari <span class="handover-blank handover-day" aria-hidden="true"></span>, tanggal <span class="handover-blank handover-date" aria-hidden="true"></span>, sesuai dengan :</p>
    <dl class="handover-fields handover-order">
        <dt>Nomor Surat Pesanan</dt><dd>: <span class="handover-manual-number" aria-hidden="true"></span>{{ config('handover.order_number_suffix') }}{{ $document->receipt_date?->year }}</dd>
        <dt>Tanggal</dt><dd>: <span class="handover-blank handover-date" aria-hidden="true"></span></dd>
    </dl>
    <p>Yang bertanda tangan di bawah ini :</p>
    <dl class="handover-fields handover-inspector">
        <dt>Nama</dt><dd>: {{ config('voucher.fixed_fields.approver_name') }}</dd>
        <dt>Jabatan</dt><dd>: {{ config('voucher.fixed_fields.approver_title') }}</dd>
        <dt>Alamat</dt><dd>: {{ config('handover.inspector_address') }}</dd>
    </dl>
    <p>Telah melakukan pemeriksaan terhadap hasil pekerjaan yang dilakukan sesuai dengan rincian pesanan yang dimaksud dan dalam jumlah yang lengkap serta kondisi yang baik.</p>
    <p class="handover-detail-label">Rincian Pekerjaan :@if($handoverPages->count() > 1) <span>(bagian {{ $handoverPageNumber }} dari {{ $handoverPages->count() }})</span>@endif</p>
    <table class="handover-items">
        <colgroup><col class="handover-item-number"><col class="handover-item-name"><col><col><col></colgroup>
        <thead><tr><th>No.</th><th>Nama Barang/Jasa</th><th>Jumlah Dipesan</th><th>Jumlah Diterima Kondisi Baik</th><th>Jumlah Diterima Kondisi Rusak</th></tr></thead>
        <tbody>
        @foreach($handoverRows as $row)
            <tr><td>{{ $row['number'] }}.</td><td>{{ $row['name'] }}</td><td></td><td></td><td></td></tr>
        @endforeach
        @for($blankRow = $handoverRows->count(); $blankRow < 3; $blankRow++)
            <tr><td>{{ $handoverRows->isEmpty() ? $blankRow + 1 . '.' : '' }}</td><td></td><td></td><td></td><td></td></tr>
        @endfor
        </tbody>
    </table>
    <p class="handover-closing">Demikian berita acara ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana seharusnya.</p>
    <div class="handover-signatures">
        <div class="handover-signing-date">{{ config('voucher.fixed_fields.payment_place') }}, <span class="handover-blank handover-date" aria-hidden="true"></span></div>
        <div class="handover-signature"><p>Penyedia,</p><div class="handover-signature-space"></div><p><span class="handover-blank handover-supplier" aria-hidden="true"></span></p></div>
        <div class="handover-signature"><p>Pemeriksa,</p><div class="handover-signature-space"></div><p>{{ config('voucher.fixed_fields.approver_name') }}</p></div>
    </div>
</div>
