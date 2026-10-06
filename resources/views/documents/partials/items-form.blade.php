@php
    $defaultItems = $document->exists ? $document->items->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->all() : [];
    $formItems = old('items', $defaultItems);
    $formItems = is_array($formItems) ? array_values(array_filter($formItems, 'is_array')) : [];
    $removedItemIds = array_intersect($document->items->pluck('id')->all(), array_filter((array) old('remove_items', []), 'is_numeric'));
    foreach ($removedItemIds as $removedId) {
        $saved = $document->items->firstWhere('id', $removedId);
        if (! collect($formItems)->contains(fn ($item) => ($item['id'] ?? null) == $saved->id)) {
            $formItems[] = ['id' => $saved->id, 'name' => $saved->name];
        }
    }
@endphp
<section class="panel" id="foto-nota"><div class="panel-heading"><span class="section-number">02</span><div><h2>Item dalam nota</h2><p>Tambahkan nama item, lalu lengkapi foto dan keterangan untuk masing-masing item.</p></div><span class="count-badge" id="item-count">{{ count($formItems) }} item</span></div>
    <input type="hidden" name="items_present" value="1">
    <div id="removed-item-inputs">@foreach($removedItemIds as $removedId)<input type="hidden" name="remove_items[]" value="{{ $removedId }}" data-removed-item="{{ $removedId }}">@endforeach</div>
    <div class="upload-error" id="items-error" role="alert" hidden></div>
    <div id="item-list">@foreach($formItems as $itemData)
        @php($savedItem = is_numeric($itemData['id'] ?? null) ? $document->items->firstWhere('id', $itemData['id']) : null)
        @include('documents.partials.item-editor', ['itemKey' => 'item_'.$loop->index, 'itemData' => $itemData, 'savedItem' => $savedItem])
    @endforeach</div>
    <p class="items-empty-hint" id="items-empty-hint">Belum ada item. Klik Tambah item untuk mulai, atau simpan nota dan lengkapi nanti.</p>
    <div class="item-add-row"><button type="button" id="add-item" class="button secondary"><x-icon name="plus" size="18"/>Tambah item</button><small>Maksimal 50 item dan 20 foto per nota.</small></div>
</section>
<template id="item-template">@include('documents.partials.item-editor', ['itemKey' => '__KEY__', 'itemData' => [], 'savedItem' => null])</template>
