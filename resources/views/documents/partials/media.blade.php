@php
    $shared = $shared ?? false;
    $routeDocument = $shared ? $document->share_token : $document;
    $receiptUrl = route($shared ? 'shared.receipt-image' : 'documents.receipt-image', $routeDocument);
@endphp
@if($document->receipt_image_path)
    <section class="panel receipt-display"><div class="section-heading"><h2>Gambar nota</h2><span class="muted small">Bukti nota yang diunggah</span></div><a class="receipt-photo" href="{{ $receiptUrl }}" target="_blank" rel="noopener"><img src="{{ $receiptUrl }}" alt="Gambar nota {{ $document->receipt_number }}" loading="lazy"></a></section>
@endif
<div class="section-heading nota-photo-heading"><h2>Item dalam nota <span class="count-badge">{{ $document->items->count() }} item</span></h2><span class="muted small">{{ $shared ? 'Dokumentasi per item' : 'Lengkapi item dan foto melalui Edit nota.' }}</span></div>
@forelse($document->items as $item)
    <section class="item-documentation"><div class="section-heading"><h3>{{ $item->name }}</h3><span class="count-badge">{{ $item->photos->count() }} foto</span></div><div class="photo-gallery">
    @forelse($item->photos as $photo)
        @php($photoUrl = $shared ? route('shared.photo', ['document' => $routeDocument, 'photo' => $photo]) : route('photos.show', $photo))
        <figure class="gallery-card"><a href="{{ $photoUrl }}" target="_blank" rel="noopener" aria-label="Buka foto {{ $item->name }} dalam ukuran asli"><img src="{{ $photoUrl }}" alt="{{ $photo->caption ?: $item->name }}" loading="lazy"></a><figcaption><span class="eyebrow">FOTO {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><p>{{ $photo->caption ?: 'Keterangan belum diisi.' }}</p></figcaption></figure>
    @empty
        <div class="nota-empty"><x-icon name="image" size="30"/><h3>Foto item bisa menyusul.</h3><p>{{ $shared ? 'Belum ada foto untuk item ini.' : 'Buka Edit nota dan pilih foto pada item ini.' }}</p></div>
    @endforelse
    </div></section>
@empty
    <div class="nota-empty"><x-icon name="image" size="36"/><h3>Nota tersimpan, gambar bisa menyusul.</h3><p>{{ $shared ? 'Belum ada item dalam nota ini.' : 'Buka Edit nota, tambahkan item, lalu lengkapi fotonya.' }}</p></div>
@endforelse
