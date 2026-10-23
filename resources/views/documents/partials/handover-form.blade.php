<section class="panel" id="berita-acara-serah-terima">
    <div class="panel-heading"><span class="section-number">03</span><div><h2>Berita Acara Serah Terima</h2><p>Lengkapi data serah terima untuk hasil cetak. Jumlah barang diisi pada masing-masing item di atas.</p></div></div>
    <div class="fields-row">
        <div class="field"><label for="handover-number">Nomor berita acara</label><input id="handover-number" name="handover_number" value="{{ old('handover_number', $document->handover_number) }}" maxlength="50" placeholder="Contoh: 001"><small>Isi bagian awal nomor. Hasil cetak dilengkapi {{ config('handover.number_suffix') }}tahun serah terima atau tahun nota.</small></div>
        <div class="field"><label for="handover-date">Tanggal serah terima</label><input type="date" id="handover-date" name="handover_date" value="{{ old('handover_date', $document->handover_date?->format('Y-m-d')) }}"><small>Hari dan tanggal pada berita acara mengikuti tanggal ini.</small></div>
    </div>
    <div class="fields-row">
        <div class="field"><label for="order-number">Nomor surat pesanan</label><input id="order-number" name="order_number" value="{{ old('order_number', $document->order_number) }}" maxlength="50" placeholder="Contoh: 001"><small>Isi bagian awal nomor. Hasil cetak dilengkapi {{ config('handover.order_number_suffix') }}tahun surat pesanan atau tahun nota.</small></div>
        <div class="field"><label for="order-date">Tanggal surat pesanan</label><input type="date" id="order-date" name="order_date" value="{{ old('order_date', $document->order_date?->format('Y-m-d')) }}"></div>
    </div>
    <div class="field"><label for="supplier-name">Nama penyedia</label><input id="supplier-name" name="supplier_name" value="{{ old('supplier_name', $document->supplier_name) }}" maxlength="150" placeholder="Nama penanda tangan dari penyedia"><small>Ditampilkan di bagian tanda tangan penyedia. Data pemeriksa terisi otomatis dari data sekolah.</small></div>
    <small>Semua kolom dapat dilengkapi nanti. Kolom yang belum diisi tetap kosong pada hasil cetak.</small>
</section>
