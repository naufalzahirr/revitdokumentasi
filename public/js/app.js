document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});

const documentForm = document.getElementById('document-form');
if (documentForm) {
    const receiptDate = document.getElementById('receipt_date');
    const paymentDate = document.getElementById('payment-date');
    receiptDate.addEventListener('input', () => { paymentDate.value = receiptDate.value; });

    const receiptInput = document.getElementById('receipt-image');
    const receiptPreview = document.getElementById('receipt-preview');
    const receiptImage = document.getElementById('receipt-preview-image');
    const receiptError = document.getElementById('receipt-upload-error');
    const removeReceipt = document.getElementById('remove-receipt-image');
    const originalReceipt = receiptImage.getAttribute('src');
    let receiptUrl;
    const updateReceiptPreview = () => {
        if (receiptUrl) URL.revokeObjectURL(receiptUrl);
        receiptError.hidden = true;
        const file = receiptInput.files[0];
        if (file && (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024)) {
            receiptInput.value = '';
            receiptError.textContent = 'Gunakan gambar nota JPG, PNG, atau WebP dengan ukuran maksimal 5 MB.';
            receiptError.hidden = false;
        }
        const validFile = receiptInput.files[0];
        if (validFile) {
            receiptUrl = URL.createObjectURL(validFile);
            receiptImage.src = receiptUrl;
            receiptPreview.hidden = false;
            if (removeReceipt) removeReceipt.checked = false;
        } else {
            if (originalReceipt) receiptImage.src = originalReceipt;
            receiptPreview.hidden = !originalReceipt || Boolean(removeReceipt?.checked);
        }
    };
    receiptInput.addEventListener('change', updateReceiptPreview);
    removeReceipt?.addEventListener('change', () => {
        if (removeReceipt.checked) receiptInput.value = '';
        updateReceiptPreview();
    });
    updateReceiptPreview();
    const picker = document.getElementById('photo-picker');
    const list = document.getElementById('photo-list');
    const zone = document.getElementById('upload-zone');
    const countLabel = document.getElementById('photo-count');
    const emptyHint = document.getElementById('photo-empty-hint');
    const errorBox = document.getElementById('upload-error');
    let nextId = 0;
    const photoCount = () => list.querySelectorAll('.photo-editor:not(.pending-removal)').length;
    const updateCount = () => {
        countLabel.textContent = `${photoCount()} foto`;
        emptyHint.hidden = photoCount() > 0;
    };
    const showError = message => {
        errorBox.textContent = message;
        errorBox.hidden = !message;
    };
    const addFiles = files => {
        const errors = [];
        for (const file of files) {
            if (photoCount() >= 20) { errors.push('Maksimal 20 foto dalam satu nota.'); break; }
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                errors.push(`${file.name}: gunakan foto JPG, PNG, atau WebP.`); continue;
            }
            if (file.size > 5 * 1024 * 1024) {
                errors.push(`${file.name}: ukuran maksimal 5 MB.`); continue;
            }
            const id = nextId++;
            const card = document.createElement('div');
            card.className = 'photo-editor new-photo';
            // Static markup only; filenames are assigned with textContent below.
            card.innerHTML = '<img class="photo-preview"><div class="photo-editor-body"><div class="photo-editor-heading"><strong></strong><button type="button" class="remove-new" aria-label="Hapus gambar baru" title="Hapus gambar">✕</button></div><label>Keterangan gambar</label><textarea rows="3" maxlength="500" placeholder="Contoh: Proses pemasangan rangka atap ruang kelas."></textarea><small>Opsional. Bisa dilengkapi nanti, maksimal 500 karakter.</small></div>';
            const image = card.querySelector('img');
            const previewUrl = URL.createObjectURL(file);
            image.src = previewUrl;
            image.alt = file.name;
            card.querySelector('strong').textContent = file.name;
            const textarea = card.querySelector('textarea');
            textarea.name = `photos[${id}][caption]`;
            textarea.id = `new-caption-${id}`;
            card.querySelector('label').htmlFor = textarea.id;
            const input = document.createElement('input');
            input.type = 'file';
            input.name = `photos[${id}][file]`;
            input.hidden = true;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            card.append(input);
            card.querySelector('.remove-new').addEventListener('click', () => {
                URL.revokeObjectURL(previewUrl);
                card.remove();
                updateCount();
            });
            list.append(card);
        }
        showError(errors.join(' '));
        updateCount();
    };
    document.getElementById('choose-photos').addEventListener('click', () => picker.click());
    picker.addEventListener('change', () => { addFiles(picker.files); picker.value = ''; });
    ['dragenter', 'dragover'].forEach(type => zone.addEventListener(type, event => {
        event.preventDefault(); zone.classList.add('dragging');
    }));
    ['dragleave', 'drop'].forEach(type => zone.addEventListener(type, event => {
        event.preventDefault(); zone.classList.remove('dragging');
    }));
    zone.addEventListener('drop', event => addFiles(event.dataTransfer.files));
    list.querySelectorAll('.remove-existing').forEach(checkbox => {
        const toggle = () => {
            const card = checkbox.closest('.photo-editor');
            card.classList.toggle('pending-removal', checkbox.checked);
            card.querySelector('textarea').disabled = checkbox.checked;
            updateCount();
        };
        checkbox.addEventListener('change', toggle);
        toggle();
    });
    const submit = documentForm.querySelector('button[type="submit"]');
    const submitLabel = submit.innerHTML;
    window.addEventListener('pageshow', () => {
        submit.disabled = false;
        submit.innerHTML = submitLabel;
    });
    documentForm.addEventListener('submit', () => {
        submit.disabled = true;
        submit.textContent = 'Menyimpan…';
    });
    updateCount();
}

const copyShareButton = document.getElementById('copy-share-link');
if (copyShareButton) {
    copyShareButton.addEventListener('click', async () => {
        const input = document.getElementById('share-url');
        const status = document.getElementById('share-status');
        try {
            if (!navigator.clipboard) throw new Error('Clipboard unavailable');
            await navigator.clipboard.writeText(input.value);
            status.textContent = 'Tautan berhasil disalin. Anda bisa membagikannya kepada penerima.';
        } catch {
            input.focus();
            input.select();
            const copied = document.execCommand('copy');
            status.textContent = copied ? 'Tautan berhasil disalin.' : 'Tautan sudah dipilih. Salin dengan Ctrl+C atau ⌘C.';
        }
    });
}
