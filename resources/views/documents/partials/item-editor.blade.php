@php
    $itemData = is_array($itemData) ? $itemData : [];
    $itemName = is_string($itemData['name'] ?? null) ? $itemData['name'] : '';
    $itemExisting = is_array($itemData['existing'] ?? null) ? $itemData['existing'] : [];
    $removedPhotos = is_array($itemData['remove_photos'] ?? null) ? $itemData['remove_photos'] : [];
@endphp
<article class="item-editor" data-item-key="{{ $itemKey }}" @if($savedItem) data-saved-id="{{ $savedItem->id }}" @endif @if($savedItem && in_array($savedItem->id, (array) old('remove_items', []))) data-pending-remove="1" @endif>
    <div class="item-editor-heading"><strong data-item-title>Item nota</strong><button type="button" class="text-button remove-item">Hapus item</button></div>
    @if($savedItem)<input type="hidden" name="items[{{ $itemKey }}][id]" value="{{ $savedItem->id }}">@endif
    <div class="field"><label for="item-name-{{ $itemKey }}">Nama item <span>*</span></label><input id="item-name-{{ $itemKey }}" class="item-name" name="items[{{ $itemKey }}][name]" value="{{ $itemName }}" required maxlength="200" placeholder="Contoh: Semen, pasir, atau rangka atap"></div>
    <p class="item-removal-message" hidden>Item dan semua fotonya akan dihapus saat perubahan disimpan.</p>
    <div class="upload-zone item-upload-zone"><span class="upload-icon"><x-icon name="upload" size="24"/></span><strong>Tambahkan foto untuk item ini</strong><p>JPG, PNG, WebP · Maksimal 5 MB/foto</p><button type="button" class="button secondary choose-item-photos">Pilih foto</button><input type="file" class="item-photo-picker" accept="image/jpeg,image/png,image/webp" multiple hidden><small>Setiap foto memiliki kolom keterangan sendiri. Foto bisa ditambahkan nanti.</small></div>
    <div class="photo-editor-list item-photo-list">
        @if($savedItem)
            @foreach($savedItem->photos as $photo)
                @php($captionData = is_array($itemExisting[$photo->id] ?? null) ? $itemExisting[$photo->id] : [])
                @php($caption = array_key_exists('caption', $captionData) && (is_string($captionData['caption']) || is_null($captionData['caption'])) ? ($captionData['caption'] ?? '') : $photo->caption)
                <div class="photo-editor existing-photo" data-photo-id="{{ $photo->id }}"><img class="photo-preview" src="{{ route('photos.show', $photo) }}" alt="Foto item {{ $loop->iteration }}"><div class="photo-editor-body"><div class="photo-editor-heading"><strong>Foto tersimpan {{ $loop->iteration }}</strong><label class="remove-label"><input type="checkbox" name="items[{{ $itemKey }}][remove_photos][]" value="{{ $photo->id }}" class="remove-existing" @checked(in_array($photo->id, $removedPhotos))> Hapus foto</label></div><label for="caption-{{ $itemKey }}-{{ $photo->id }}">Keterangan gambar</label><textarea id="caption-{{ $itemKey }}-{{ $photo->id }}" name="items[{{ $itemKey }}][existing][{{ $photo->id }}][caption]" rows="3" maxlength="500">{{ $caption }}</textarea><small>Opsional. Bisa dilengkapi nanti, maksimal 500 karakter.</small></div></div>
            @endforeach
        @endif
    </div>
    <p class="photo-empty-hint item-photo-hint">Belum ada foto. Item tetap bisa disimpan dan dilengkapi nanti.</p>
    <span class="count-badge item-photo-count">{{ $savedItem?->photos->count() ?? 0 }} foto</span>
</article>
