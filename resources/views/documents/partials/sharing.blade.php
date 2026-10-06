<section class="panel sharing-panel">
    <div class="section-heading"><h2>Bagikan nota</h2><span class="category-tag">Tautan untuk melihat</span></div>
    <p class="sharing-description">Penerima dapat melihat gambar nota, foto kegiatan, keterangan, dan hasil cetak melalui halaman berbagi.</p>
    @if($document->share_token)
        @php($shareUrl = route('shared.show', $document->share_token))
        <label class="share-label" for="share-url">Tautan berbagi</label>
        <div class="share-link-row"><input id="share-url" type="url" value="{{ $shareUrl }}" readonly><button type="button" class="button primary" id="copy-share-link">Salin tautan</button><a class="button secondary" href="{{ $shareUrl }}" target="_blank" rel="noopener">Lihat halaman</a></div>
        <div class="share-bottom"><span class="muted small" id="share-status" role="status">Siapa pun yang memiliki tautan dapat melihat isi nota ini.</span><form method="post" action="{{ route('documents.unshare', $document) }}" data-confirm="Nonaktifkan tautan berbagi? Penerima tidak dapat membuka tautan lama lagi.">@csrf @method('DELETE')<button type="submit" class="text-button">Nonaktifkan tautan</button></form></div>
    @else
        <form method="post" action="{{ route('documents.share', $document) }}">@csrf<button class="button primary" type="submit">Buat tautan berbagi</button></form>
    @endif
    @if(in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1'], true))<p class="sharing-local-note">Aplikasi masih berjalan lokal. Setelah dipasang di hosting, tautan akan menggunakan alamat hosting dan dapat dibuka orang lain melalui internet.</p>@endif
</section>
