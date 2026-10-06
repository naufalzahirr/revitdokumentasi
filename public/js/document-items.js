(() => {
    const form = document.getElementById('document-form');
    const list = document.getElementById('item-list');
    if (!form || !list) return;
    const template = document.getElementById('item-template');
    const removedInputs = document.getElementById('removed-item-inputs');
    const errorBox = document.getElementById('items-error');
    let nextItem = 0;
    const activeItems = () => Array.from(list.querySelectorAll('.item-editor:not(.pending-item-removal)'));
    const photoCount = () => activeItems().reduce((count, card) => count + card.querySelectorAll('.photo-editor:not(.pending-removal)').length, 0);
    const showError = message => { errorBox.textContent = message; errorBox.hidden = !message; };
    const updateCounts = () => {
        const cards = activeItems();
        document.getElementById('item-count').textContent = `${cards.length} item · ${photoCount()} foto`;
        document.getElementById('items-empty-hint').hidden = cards.length > 0;
        cards.forEach((card, index) => {
            const count = card.querySelectorAll('.photo-editor:not(.pending-removal)').length;
            card.querySelector('[data-item-title]').textContent = `Item ${index + 1}`;
            card.querySelector('.item-photo-count').textContent = `${count} foto`;
            card.querySelector('.item-photo-hint').hidden = count > 0;
        });
    };
    const togglePhoto = checkbox => {
        const photo = checkbox.closest('.photo-editor');
        photo.classList.toggle('pending-removal', checkbox.checked);
        photo.querySelector('textarea').disabled = checkbox.checked || Boolean(photo.closest('.pending-item-removal'));
    };
    const setItemRemoved = (card, removed) => {
        card.classList.toggle('pending-item-removal', removed);
        card.querySelector('.item-removal-message').hidden = !removed;
        card.querySelector('.remove-item').textContent = removed ? 'Batalkan penghapusan' : 'Hapus item';
        card.querySelectorAll('input, textarea, select, button:not(.remove-item)').forEach(input => { input.disabled = removed; });
        card.querySelectorAll('.remove-existing').forEach(togglePhoto);
        const id = card.dataset.savedId;
        const input = removedInputs.querySelector(`input[data-removed-item="${id}"]`);
        if (removed && !input) {
            const flag = document.createElement('input');
            flag.type = 'hidden'; flag.name = 'remove_items[]'; flag.value = id; flag.dataset.removedItem = id;
            removedInputs.append(flag);
        } else if (!removed && input) input.remove();
        updateCounts();
    };
    const initItem = card => {
        const key = card.dataset.itemKey;
        const photos = card.querySelector('.item-photo-list');
        const picker = card.querySelector('.item-photo-picker');
        const zone = card.querySelector('.item-upload-zone');
        let nextPhoto = 0;
        const addFiles = files => {
            if (card.classList.contains('pending-item-removal')) return;
            const errors = [];
            for (const file of files) {
                if (photoCount() >= 20) { errors.push('Maksimal 20 foto dalam satu nota, termasuk semua item.'); break; }
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                    errors.push(`${file.name}: gunakan foto JPG, PNG, atau WebP.`); continue;
                }
                if (file.size > 5 * 1024 * 1024) { errors.push(`${file.name}: ukuran maksimal 5 MB.`); continue; }
                const index = nextPhoto++;
                const photo = document.createElement('div'); photo.className = 'photo-editor new-photo';
                // Static markup only; filenames are assigned through textContent.
                photo.innerHTML = '<img class="photo-preview"><div class="photo-editor-body"><div class="photo-editor-heading"><strong></strong><button type="button" class="remove-new" aria-label="Hapus foto baru" title="Hapus foto">✕</button></div><label>Keterangan gambar</label><textarea rows="3" maxlength="500" placeholder="Tuliskan keterangan foto item ini."></textarea><small>Opsional. Bisa dilengkapi nanti, maksimal 500 karakter.</small></div>';
                const url = URL.createObjectURL(file);
                const image = photo.querySelector('img'); image.src = url; image.alt = file.name;
                photo.querySelector('strong').textContent = file.name;
                const caption = photo.querySelector('textarea');
                caption.name = `items[${key}][photos][${index}][caption]`; caption.id = `new-caption-${key}-${index}`;
                photo.querySelector('label').htmlFor = caption.id;
                const input = document.createElement('input'); input.type = 'file'; input.hidden = true;
                input.name = `items[${key}][photos][${index}][file]`;
                const transfer = new DataTransfer(); transfer.items.add(file); input.files = transfer.files;
                photo.append(input);
                photo.querySelector('.remove-new').addEventListener('click', () => { URL.revokeObjectURL(url); photo.remove(); updateCounts(); });
                photos.append(photo); updateCounts();
            }
            showError(errors.join(' '));
        };
        card.querySelector('.choose-item-photos').addEventListener('click', () => picker.click());
        picker.addEventListener('change', () => { addFiles(picker.files); picker.value = ''; });
        ['dragenter', 'dragover'].forEach(type => zone.addEventListener(type, event => {
            event.preventDefault(); if (!card.classList.contains('pending-item-removal')) zone.classList.add('dragging');
        }));
        ['dragleave', 'drop'].forEach(type => zone.addEventListener(type, event => { event.preventDefault(); zone.classList.remove('dragging'); }));
        zone.addEventListener('drop', event => addFiles(event.dataTransfer.files));
        card.querySelectorAll('.remove-existing').forEach(checkbox => {
            checkbox.addEventListener('change', () => { togglePhoto(checkbox); updateCounts(); }); togglePhoto(checkbox);
        });
        card.querySelector('.remove-item').addEventListener('click', () => {
            if (card.dataset.savedId) setItemRemoved(card, !card.classList.contains('pending-item-removal'));
            else {
                card.querySelectorAll('.new-photo img').forEach(image => URL.revokeObjectURL(image.src));
                card.remove(); updateCounts();
            }
            showError('');
        });
        if (card.dataset.pendingRemove) setItemRemoved(card, true);
    };
    list.querySelectorAll('.item-editor').forEach(initItem);
    document.getElementById('add-item').addEventListener('click', () => {
        if (activeItems().length >= 50) { showError('Maksimal 50 item dalam satu nota.'); return; }
        const fragment = document.createElement('template');
        fragment.innerHTML = template.innerHTML.replaceAll('__KEY__', `new_${nextItem++}`);
        const card = fragment.content.firstElementChild; list.append(card); initItem(card); updateCounts();
        showError(''); card.querySelector('.item-name').focus();
    });
    updateCounts();
})();
