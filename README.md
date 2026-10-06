# Revita — Dokumentasi Revitalisasi

Aplikasi Laravel 12 sederhana untuk mengarsipkan foto revitalisasi dan mencetak laporan A4. Tampilan berbahasa Indonesia, database SQLite, tanpa proses build frontend.

## Fitur

- Tambah, lihat, edit, dan hapus nota. Satu nota dapat memuat beberapa gambar.
- Menu kategori untuk menambah, mengedit, dan menghapus kategori yang belum digunakan; pilihan kategori berbentuk dropdown pada formulir nota. Kategori lama otomatis dipertahankan saat migrasi. Tanggal nota, nomor nota, dan unggahan gambar/scan nota. Gambar nota dapat diganti atau dihapus saat edit.
- Unggah beberapa foto dengan keterangan masing-masing; JPG, PNG, WebP, maksimal 5 MB per foto dan 20 foto per nota. Nota boleh disimpan tanpa foto dan keterangan boleh dilengkapi kemudian.
- Cari berdasarkan nomor nota atau kategori, filter kategori, dan pagination.
- Satu berkas cetak/PDF per nota, berurutan: **Bukti Pengeluaran Dana → Foto Nota → Dokumentasi Revitalisasi**. Dokumentasi berjudul di tengah, maksimal dua foto per halaman A4; foto ketiga dan seterusnya dilanjutkan ke halaman berikutnya. Identitas nota diulang pada halaman foto nota dan dokumentasi. Bagian yang belum memiliki gambar menampilkan penanda kosong.
- Bagian **Bukti Pengeluaran Dana**: mengikuti contoh formulir, dengan nominal dan terbilang otomatis, keperluan, serta tiga kolom tanda tangan (pemberi persetujuan, bendahara, penerima). Nomor bukti berurutan otomatis (`001/REV.SMK`, `002/REV.SMK`, dst.). Kepala sekolah, nama/NIP Yayuk, bendahara Riri dan NIP, serta tempat pembayaran otomatis; tanggal pembayaran mengikuti tanggal nota. Nominal, keperluan, nama penerima pembayaran, dan NIP penerima diisi per nota. NIP penerima opsional; jika kosong, baris NIP tetap disediakan pada hasil cetak.
- Buat dan salin tautan berbagi. Halaman penerima menampilkan nota beserta gambarnya dan tombol cetak berkas gabungan, tanpa tombol edit/hapus. Tautan dapat dinonaktifkan; tautan yang dibuat ulang berbeda dari tautan lama.
- Cetak ke printer atau simpan PDF melalui dialog cetak browser.
- Penghapusan nota/foto juga menghapus berkas dari penyimpanan.

## Instalasi dan menjalankan aplikasi

Persyaratan: PHP 8.2+, Composer, ekstensi PDO SQLite, fileinfo, mbstring, XML, dan GD untuk pengujian.

```bash
git clone https://github.com/naufalzahirr/revitdokumentasi.git
cd revitdokumentasi
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
bash scripts/start.sh
```

Buka **http://127.0.0.1:8005**. Port lain dapat dipilih dengan `bash scripts/start.sh 8006`.

Untuk proyek yang sudah terpasang, jalankan `php artisan migrate` setelah menarik pembaruan, kemudian `bash scripts/start.sh` dari folder proyek.

Skrip ini mengatur batas unggahan PHP untuk 20 foto kegiatan dan satu gambar nota, maksimal 5 MB per gambar. Tidak perlu `npm install`, `npm run dev`, atau `php artisan storage:link`.

File `.env`, database SQLite, foto unggahan, dan backup lokal tidak disertakan dalam repository. Instalasi baru dimulai dengan arsip kosong.

## Cara pakai

1. Klik **Tambah nota**.
2. Pilih kategori pembangunan dari dropdown, lalu isi tanggal nota dan nomor nota. Untuk jenis pembangunan baru, tambahkan melalui menu **Kategori** terlebih dahulu.
3. Klik **Pilih foto** atau tarik foto ke area unggah. Bisa pilih beberapa foto sekaligus; kolom keterangan muncul otomatis untuk setiap foto. Nota juga bisa disimpan terlebih dahulu tanpa gambar. Keterangan gambar bersifat opsional.
4. Klik **Simpan nota**. Untuk menambahkan gambar berikutnya ke nota yang sama, buka **Edit nota**, klik **Pilih foto**, lalu **Simpan perubahan**. Gambar lama tetap tersimpan.
5. Gunakan **Edit nota** untuk memperbaiki identitas nota atau keterangan, Isi bagian **Bukti pengeluaran dana** jika ingin menggunakan format bukti pembayaran. Pada detail nota, klik **Cetak berkas nota** untuk mencetak seluruh bagian dalam satu berkas. Klik **Cetak / Simpan PDF**, pilih A4 dengan skala 100%, dan matikan header/footer bawaan browser. Untuk PDF, pilih **Save as PDF**.

Urutan foto mengikuti urutan unggah. Jika validasi gagal, foto baru harus dipilih kembali karena browser tidak mempertahankan input berkas setelah halaman dimuat ulang.

## Tautan berbagi

Klik **Buat tautan berbagi** pada detail nota, lalu **Salin tautan**. Penerima tidak perlu login untuk membuka halaman berbagi. Tautan memuat token acak untuk satu nota dan dapat dinonaktifkan dari detail nota.

Selama aplikasi berjalan di `127.0.0.1`, tautan hanya bekerja pada komputer yang menjalankan aplikasi. Setelah hosting siap, atur `APP_URL=https://domain-anda`, jalankan `php artisan migrate --force` dan `php artisan optimize:clear`; buka aplikasi melalui domain tersebut sebelum menyalin tautan. Tidak ada layanan tunnel atau publikasi otomatis.

Halaman berbagi tidak menyediakan operasi edit/hapus. Aplikasi pengelola saat ini belum memiliki login; tambahkan autentikasi pada rute pengelola sebelum hosting dibuka ke internet.

## Penyimpanan dan backup

Database: `database/database.sqlite`. Foto: `storage/app/private/documents/`. Backup keduanya agar arsip beserta fotonya dapat dipulihkan.

Aplikasi ini untuk penggunaan lokal, tanpa login. Untuk penggunaan melalui internet, tambahkan autentikasi dan gunakan server web produksi dengan document root `public`, `APP_DEBUG=false`, serta batas PHP `upload_max_filesize=6M`, `post_max_size=128M`, `max_file_uploads=21`. Server web juga harus menerima request hingga 128 MB.

## Pengujian

```bash
php artisan test
vendor/bin/pint --test
```

Pengujian menggunakan SQLite in-memory dan penyimpanan foto palsu, sehingga arsip asli tidak diubah.

Data sekolah otomatis tersimpan dalam `config/voucher.php`. Penghitung nomor bukti disimpan di database dan tidak mundur saat nota dihapus. Nomor tidak berubah ketika nota diedit.
