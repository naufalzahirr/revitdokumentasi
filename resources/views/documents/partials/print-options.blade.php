@php
    $shared = $shared ?? false;
    $routeDocument = $shared ? $document->share_token : $document;
@endphp
<section class="print-options"><div class="section-heading"><h2>Pilih dokumen yang dicetak</h2><span class="muted small">Dua format cetak terpisah</span></div><div class="print-option-grid">
    <a class="print-option" href="{{ route($shared ? 'shared.print' : 'documents.print', $routeDocument) }}"><span class="stat-icon green"><x-icon name="image" size="24"/></span><div><strong>Dokumentasi Revitalisasi</strong><p>Foto kegiatan, keterangan, dan lampiran gambar nota.</p></div><x-icon name="arrow-right" size="18"/></a>
    <a class="print-option" href="{{ route($shared ? 'shared.voucher' : 'documents.voucher', $routeDocument) }}"><span class="stat-icon amber"><x-icon name="file" size="24"/></span><div><strong>Bukti Pengeluaran Dana</strong><p>Nominal, terbilang, keperluan, dan kolom tanda tangan.</p></div><x-icon name="arrow-right" size="18"/></a>
</div></section>
