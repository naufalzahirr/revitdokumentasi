@php
    $shared = $shared ?? false;
    $routeDocument = $shared ? $document->share_token : $document;
@endphp
<section class="print-options"><div class="section-heading"><h2>Berkas cetak nota</h2><span class="muted small">Semua dalam satu PDF</span></div>
    <a class="print-option" href="{{ route($shared ? 'shared.print' : 'documents.print', $routeDocument) }}"><span class="stat-icon green"><x-icon name="print" size="24"/></span><div><strong>Cetak berkas nota</strong><p>1. Bukti pengeluaran · 2. Foto nota · 3. Dokumentasi<br>Dokumentasi maksimal dua foto per halaman, sisanya dilanjutkan ke halaman berikutnya.</p></div><x-icon name="arrow-right" size="18"/></a>
</section>
