# Revita — Dokumentasi Revitalisasi

Aplikasi Laravel 12 sederhana untuk mengarsipkan foto revitalisasi dan mencetak laporan A4. Tampilan berbahasa Indonesia, mendukung database SQLite dan MySQL, tanpa proses build frontend.

## Fitur

- Login menggunakan username dan password. Akun pengelola dapat mengelola seluruh nota dan kategori; admin juga dapat mengelola akun.
- Admin dapat menambah akun, mengubah nama/username/peran, menonaktifkan akun, dan mereset password. Akun admin sendiri tidak dapat dinonaktifkan atau diturunkan perannya.
- Password awal wajib diganti saat login pertama. Reset password dan perubahan status akun membatalkan sesi lama. Percobaan login berulang dibatasi; halaman internal dan foto meminta login.

- Tambah, lihat, edit, dan hapus nota. Satu nota berisi beberapa item (misalnya semen, pasir, atau rangka atap); setiap item dapat memiliki beberapa foto dengan keterangan masing-masing.
- Menu kategori untuk menambah, mengedit, dan menghapus kategori yang belum digunakan; pilihan kategori berbentuk dropdown pada formulir nota. Kategori awal menggunakan tujuh jenis kegiatan yang ditetapkan sekolah. Tanggal nota, nomor nota, dan unggahan gambar/scan nota. Gambar nota dapat diganti atau dihapus saat edit.
- Tambahkan nama item terlebih dahulu, kemudian unggah foto untuk item tersebut dengan keterangan masing-masing; JPG, PNG, WebP, maksimal 5 MB per foto, 50 item dan 20 foto secara total per nota. Nota dan item boleh disimpan tanpa foto; foto serta keterangan boleh dilengkapi kemudian. Item dapat diedit atau dihapus beserta fotonya.
- Cari berdasarkan nomor nota atau kategori, filter kategori, dan pagination.
- Pantau progres pada **Semua nota**: jumlah nota lengkap/perlu dilengkapi, persentase nota lengkap dari seluruh nota tersimpan, serta jumlah nota dengan data dan dokumentasi lengkap. Setiap kartu menampilkan bagian yang belum diisi, jumlah item yang sudah memiliki foto, dan foto yang belum diberi keterangan. Filter kelengkapan dapat digabung dengan pencarian dan kategori; tombol **Lengkapi** langsung membuka formulir edit.
- Satu berkas cetak/PDF per nota, berurutan: **Bukti Pengeluaran Dana → Foto Nota → Dokumentasi Revitalisasi**. Dokumentasi berjudul di tengah, maksimal dua foto per halaman A4; foto ketiga dan seterusnya dilanjutkan ke halaman berikutnya. Identitas nota diulang pada halaman foto nota dan dokumentasi. Bagian yang belum memiliki gambar menampilkan penanda kosong.
- Bagian **Bukti Pengeluaran Dana**: mengikuti contoh formulir, dengan nominal dan terbilang otomatis, keperluan, serta tiga kolom tanda tangan (pemberi persetujuan, bendahara, penerima). Nomor bukti berurutan otomatis (`001/REV.SMK`, `002/REV.SMK`, dst.). Kepala sekolah, nama/NIP Yayuk, bendahara Riri dan NIP, serta tempat pembayaran otomatis; tanggal pembayaran mengikuti tanggal nota. Nominal, keperluan, nama penerima pembayaran, dan NIP penerima diisi per nota. NIP penerima opsional; jika kosong, label dan nomor NIP penerima tidak ditampilkan pada hasil cetak.
- Buat dan salin tautan berbagi. Halaman penerima menampilkan nota beserta gambarnya dan tombol cetak berkas gabungan, tanpa tombol edit/hapus. Tautan dapat dinonaktifkan; tautan yang dibuat ulang berbeda dari tautan lama.
- Cetak ke printer atau simpan PDF melalui dialog cetak browser.
- Penghapusan nota/foto juga menghapus berkas dari penyimpanan.

## Instalasi dan menjalankan aplikasi

Persyaratan: PHP 8.2+, Composer, ekstensi PDO sesuai database (SQLite atau MySQL), fileinfo, mbstring, XML, dan GD untuk pengujian.

```bash
git clone https://github.com/naufalzahirr/revitdokumentasi.git
cd revitdokumentasi
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan revita:setup-users
bash scripts/start.sh
```

Buka **http://127.0.0.1:8005**. Port lain dapat dipilih dengan `bash scripts/start.sh 8006`.

Untuk proyek yang sudah terpasang, jalankan `php artisan migrate` setelah menarik pembaruan, kemudian `bash scripts/start.sh` dari folder proyek.

Skrip ini mengatur batas unggahan PHP untuk 20 foto kegiatan dan satu gambar nota, maksimal 5 MB per gambar. Tidak perlu `npm install`, `npm run dev`, atau `php artisan storage:link`.

File `.env`, database SQLite, foto unggahan, dan backup lokal tidak disertakan dalam repository. Instalasi baru dimulai dengan arsip kosong.

## Kategori awal

- Pembangunan Baru - RPS Produksi dan Siaran Program Televisi
- Pembangunan Baru - RPS Pengembangan Gim
- Rehabilitasi Sedang - Ruang Kelas
- Rehabilitasi Sedang - Perpustakaan
- Pengecatan Ruang Lainnya
- Pengadaan Perabot - RPS Pengembangan Gim
- Pengadaan Perabot - RPS Produksi dan Siaran Program Televisi

Migrasi kategori terbaru mengganti daftar kategori sebelumnya dengan tujuh kategori ini. Setelah itu kategori masih dapat dikelola melalui menu Kategori.

## Akun awal

Setelah migrasi, jalankan `php artisan revita:setup-users`. Masukkan password sementara saat diminta; password tidak disimpan di kode repository.

Perintah ini membuat `admin` (Admin) serta `naufalzahirr`, `riri`, `yayuk`, `azizul`, `purnamawati`, dan `rika` (Pengelola). Untuk username admin berbeda, gunakan `--admin=namaadmin`. Menjalankan ulang perintah tidak menimpa akun, peran, atau password yang sudah ada. Untuk otomasi, `--password-stdin` menerima password melalui stdin.

Login di `/login`, lalu ganti password awal. Admin mengelola akun melalui menu **Pengguna**. Pengguna yang lupa password menghubungi admin; tidak ada registrasi publik atau reset melalui email.

Database lokal beserta akun tidak ikut di-push ke GitHub. Jalankan migrasi dan perintah pembuatan akun pada hosting, atau pindahkan backup database lokal jika ingin mempertahankan arsip dan akun yang sudah ada.

## Cara pakai

Login terlebih dahulu, lalu:

1. Klik **Tambah nota**.
2. Pilih kategori pembangunan dari dropdown, lalu isi tanggal nota dan nomor nota. Untuk jenis pembangunan baru, tambahkan melalui menu **Kategori** terlebih dahulu.
3. Klik **Tambah item**, isi nama item, lalu klik **Pilih foto** pada item tersebut atau tarik foto ke area unggahnya. Bisa pilih beberapa foto sekaligus; kolom keterangan muncul otomatis untuk setiap foto. Tambahkan item lain sesuai isi nota. Nota dan item bisa disimpan tanpa foto.
4. Klik **Simpan nota**. Untuk melengkapi atau mengubah item, buka **Edit nota**. Foto baru dipilih pada item yang sesuai. Klik **Tambah item** untuk item tambahan, lalu **Simpan perubahan**. Penghapusan item tersimpan dapat dibatalkan sebelum perubahan disimpan.
5. Gunakan **Edit nota** untuk memperbaiki identitas nota atau keterangan, Isi bagian **Bukti pengeluaran dana** jika ingin menggunakan format bukti pembayaran. Pada detail nota, klik **Cetak berkas nota** untuk mencetak seluruh bagian dalam satu berkas. Klik **Cetak / Simpan PDF**, pilih A4 dengan skala 100%, dan matikan header/footer bawaan browser. Untuk PDF, pilih **Save as PDF**.

Urutan item mengikuti urutan penambahan, dan foto di dalam item mengikuti urutan unggah. Nama item dicantumkan pada halaman cetak dokumentasi; item tanpa foto tetap ditampilkan dengan penanda foto belum ditambahkan. Migrasi mengelompokkan foto lama ke item “Dokumentasi sebelumnya”. Jika validasi gagal, foto baru harus dipilih kembali karena browser tidak mempertahankan input berkas setelah halaman dimuat ulang.

### Patokan kelengkapan nota

Data dinyatakan lengkap jika kategori, tanggal, nomor nota, gambar nota, nominal lebih dari nol, keperluan, dan nama penerima pembayaran sudah terisi. NIP penerima tetap opsional. Dokumentasi lengkap jika ada minimal satu item, setiap item memiliki minimal satu foto, dan setiap foto memiliki keterangan. Nota lengkap memenuhi kedua bagian tersebut. Status otomatis mengikuti isian yang tersimpan, termasuk setelah item/foto dihapus; pengisian bertahap tetap diperbolehkan. Ringkasan progres selalu mencakup seluruh nota, meskipun daftar sedang difilter atau berpindah halaman.

## Tautan berbagi

Klik **Buat tautan berbagi** pada detail nota, lalu **Salin tautan**. Penerima tidak perlu login untuk membuka halaman berbagi. Tautan memuat token acak untuk satu nota dan dapat dinonaktifkan dari detail nota.

Selama aplikasi berjalan di `127.0.0.1`, tautan hanya bekerja pada komputer yang menjalankan aplikasi. Setelah hosting siap, atur `APP_URL=https://domain-anda`, jalankan `php artisan migrate --force` dan `php artisan optimize:clear`; buka aplikasi melalui domain tersebut sebelum menyalin tautan. Tidak ada layanan tunnel atau publikasi otomatis.

Halaman berbagi tidak menyediakan operasi edit/hapus. Semua rute pengelolaan dilindungi login. Tautan berbagi tetap bisa dibuka tanpa login dan dapat dicabut dari detail nota.

## Penyimpanan dan backup

Database: `database/database.sqlite`. Foto: `storage/app/private/documents/`. Backup keduanya agar arsip beserta fotonya dapat dipulihkan.

Untuk penggunaan melalui internet, gunakan HTTPS dan server web produksi dengan document root `public`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-anda`, `SESSION_SECURE_COOKIE=true`, serta batas PHP `upload_max_filesize=6M`, `post_max_size=128M`, `max_file_uploads=21`. Server web juga harus menerima request hingga 128 MB.

## Pengujian

```bash
php artisan test
vendor/bin/pint --test
```

Pengujian menggunakan SQLite in-memory dan penyimpanan foto palsu, sehingga arsip asli tidak diubah. Pengujian mencakup login, pembatasan percobaan, ganti password pertama, hak admin, pencabutan sesi, serta akses tautan berbagi tanpa login.

Data sekolah otomatis tersimpan dalam `config/voucher.php`. Nota baru memakai nomor bukti kosong paling awal: jika `001/REV.SMK` dihapus, nomor tersebut digunakan kembali untuk nota baru, walaupun masih ada nota bernomor lebih tinggi. Nomor nota lain tidak diubah, dan nomor bukti tetap saat nota diedit. Pemberian nomor dikunci dalam transaksi database agar penyimpanan nota bersamaan tidak mendapat nomor yang sama.

## Hosting dengan MySQL dan reset instalasi awal

Untuk MySQL, isi `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai hosting, lalu jalankan `php artisan config:clear` dan `php artisan migrate --force`.

Untuk memulai ulang database aplikasi yang belum digunakan, jalankan dari folder proyek:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan migrate:fresh --force
php artisan revita:setup-users
php artisan optimize
```

`migrate:fresh` menghapus seluruh tabel dan datanya pada database yang terhubung, lalu membuat ulang tabel aplikasi. Gunakan database khusus aplikasi ini. Masukkan password awal saat perintah pembuatan akun memintanya; ketikan password tidak ditampilkan. APP_KEY dan .env tetap menggunakan konfigurasi hosting yang sudah disiapkan. Foto yang sebelumnya diunggah masih berada di penyimpanan berkas walaupun catatan databasenya direset.
