const printButton = document.getElementById('print-button');
const instruction = document.getElementById('print-instruction');
printButton.addEventListener('click', async () => {
    printButton.disabled = true;
    instruction.textContent = 'Menyiapkan semua foto…';
    try {
        await Promise.all(Array.from(document.images).map(image => image.decode()));
        instruction.textContent = 'Pilih kertas A4, skala 100%, dan nonaktifkan header/footer browser. Untuk PDF, pilih “Save as PDF”.';
        window.print();
    } catch {
        instruction.textContent = 'Ada foto yang gagal dimuat. Muat ulang halaman sebelum mencetak.';
    } finally {
        printButton.disabled = false;
    }
});
