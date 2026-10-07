@php($progress = $document->progress())
<div class="note-progress">
    <span class="completion-badge {{ $progress['complete'] ? 'complete' : 'incomplete' }}">{{ $progress['complete'] ? 'Lengkap' : 'Perlu dilengkapi' }}</span>
    <div class="note-progress-counts"><span>Data <strong>{{ $progress['data_done'] }}/{{ $progress['data_total'] }}</strong></span><span>Item dengan foto <strong>{{ $progress['items_with_photos'] }}/{{ $progress['items'] }}</strong></span></div>
    @if($progress['missing_data'])<p>Belum diisi: {{ implode(', ', $progress['missing_data']) }}.</p>@endif
    @if($progress['items'] === 0)<p>Item dan foto belum ditambahkan.</p>@elseif($progress['items_without_photos'] > 0)<p>{{ $progress['items_without_photos'] }} item belum memiliki foto.</p>@endif
    @if($progress['photos_without_caption'] > 0)<p>{{ $progress['photos_without_caption'] }} foto belum memiliki keterangan.</p>@endif
    @if($progress['complete'])<p class="completion-ready">Data, gambar nota, dan dokumentasi sudah terisi.</p>@endif
</div>
