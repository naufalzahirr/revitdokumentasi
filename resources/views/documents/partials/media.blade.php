@php
    $shared = $shared ?? false;
    $routeDocument = $shared ? $document->share_token : $document;
    $receiptUrl = route($shared ? 'shared.receipt-image' : 'documents.receipt-image', $routeDocument);
@endphp
@if($document->receipt_image_path)
    <section class="panel receipt-display"><div class="section-heading"><h2>Gambar nota</h2><span class="muted small">Bukti nota yang diunggah</span></div><a class="receipt-photo" href="{{ $receiptUrl }}" target="_blank" rel="noopener"><img src="{{ $receiptUrl }}" alt="Gambar nota {{ $document->receipt_number }}" loading="lazy"></a></section>
@endif
<div class="section-heading nota-photo-heading"><h2>Foto & keterangan <span class="count-badge">{{ $document->photos->count() }}</span></h2><span class="muted small">{{ $shared ? 'Dokumentasi kegiatan' : 'Tambahkan foto melalui Edit nota.' }}</span></div>
<div class="photo-gallery">
@forelse($document->photos as $photo)
    @php($photoUrl = $shared ? route('shared.photo', ['document' => $routeDocument, 'photo' => $photo]) : route('photos.show', $photo))
    <figure class="gallery-card"><a href="{{ $photoUrl }}" target="_blank" rel="noopener" aria-label="Buka foto {{ $loop->iteration }} dalam ukuran asli"><img src="{{ $photoUrl }}" alt="{{ $photo->caption ?: 'Foto kegiatan '.$loop->iteration }}" loading="lazy"></a><figcaption><span class="eyebrow">FOTO {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><p>{{ $photo->caption ?: 'Keterangan belum diisi.' }}</p></figcaption></figure>
@empty
    <div class="nota-empty"><x-icon name="image" size="36"/><h3>Nota tersimpan, gambar bisa menyusul.</h3><p>{{ $shared ? 'Belum ada foto kegiatan dalam nota ini.' : 'Buka Edit nota, lalu Pilih foto untuk melengkapi nota ini.' }}</p></div>
@endforelse
</div>
