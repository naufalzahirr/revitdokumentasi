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
