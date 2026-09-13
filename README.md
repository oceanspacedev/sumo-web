# Web Gais

Web Gais menggunakan Laravel 12, PHP 8.4, dan Vite 7. Endpoint, format JSON, tampilan Bootstrap, dan aturan akses aplikasi lama dipertahankan.

## Kebutuhan runtime

- PHP **8.4.x** atau versi 8.x berikutnya yang memenuhi `composer check-platform-reqs`, dengan PDO MySQL/SQLite, GD, fileinfo, mbstring, XML/DOM, cURL, dan ZIP.
- Composer 2; platform resolusi lockfile ditetapkan ke PHP `8.4.0`, tanpa mengabaikan persyaratan platform.
- Node.js **22.12+** dan npm. Vite 7 memakai ESM.
- MySQL dengan charset `utf8mb4`, collation `utf8mb4_unicode_ci`, dan strict mode sesuai `config/database.php`.
- Web server menunjuk ke direktori `public`; direktori `storage` dan `bootstrap/cache` harus dapat ditulis oleh proses aplikasi.

## Instalasi baru

```bash
cp .env.example .env
composer install
php artisan key:generate
```

Atur koneksi `DB_*` ke database kosong, lalu jalankan:

```bash
php artisan migrate
php artisan storage:link
npm ci
npm run build
```

Seeder historis tersedia melalui `php artisan db:seed` untuk database baru yang memerlukan data awal tersebut. `UserSeeder` memakai akun dan password contoh yang sudah ada di repository; sesuaikan kredensialnya sebelum dipakai untuk pengguna sebenarnya. Jangan menjalankan seeder ini pada database lama karena datanya diinsert kembali.

Gunakan `php artisan serve` dan `npm run dev` untuk pengembangan. `npm run watch` membangun ulang aset saat sumber berubah. Build produksi harus menyertakan `public/build` beserta manifest; `@vite` akan menunjukkan error jika aset belum dibangun.

Aplikasi tetap mendukung `FILESYSTEM_DRIVER`, `CACHE_DRIVER`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, dan konfigurasi environment lama. Disk lokal tetap berakar di `storage/app`, disk publik di `storage/app/public`; default cache/session tetap file dan queue tetap sync. Timezone tetap `Asia/Jakarta`, pagination tetap Bootstrap 3.

Isi `SENTRY_LARAVEL_DSN` untuk pelaporan error backend. Integrasi Sentry didaftarkan sekali melalui `bootstrap/app.php`; tidak perlu menambahkan handler atau channel Sentry kedua. Provider aplikasi berada di `bootstrap/providers.php`.

## Upgrade database lama

1. Backup database, upload, `.env`, source code, dan kedua lockfile; simpan versi rilis sebelumnya untuk rollback.
2. Siapkan rilis baru memakai PHP 8.4 dan `composer install --no-dev --optimize-autoloader`, lalu `npm ci` dan `npm run build`. Pertahankan `.env`, `APP_KEY`, dan storage yang lama.
3. Masuk maintenance, hentikan worker yang memakai kode lama, dan bersihkan cache konfigurasi lama sebelum menjalankan kode Laravel 12. Cache lama dapat merujuk kernel/provider yang sudah dihapus.
4. Pada rilis baru jalankan `php artisan migrate --force`, `php artisan storage:link` bila symlink belum ada, lalu `php artisan config:cache`, `php artisan route:cache`, dan `php artisan view:cache`.
5. Arahkan web server ke rilis baru; mulai ulang worker queue dan scheduler memakai PHP 8.4. Verifikasi login, token API, upload, dan halaman utama sebelum keluar dari maintenance.

Migration upgrade `2026_02_04_114039_add_expires_at_to_personal_access_tokens_table` hanya menambahkan `expires_at` nullable dan index. Token, tabel, dan data lama tidak dibuat ulang. Semua migration historis tetap tersedia untuk instalasi baru; jangan memakai `migrate:fresh` pada database aplikasi. Token lama dengan expiry `NULL` tetap berlaku mengikuti konfigurasi Sanctum. Token baru mendukung expiry tanpa mengubah respons endpoint login.

## Aset dan PWA

- JavaScript aplikasi: `resources/js/app.js`; registrasi service worker: `resources/js/pwa.js`.
- Scanner: `resources/js/scanqr.js` dan `resources/js/productqr.js`.
- Vendor/CDN, jQuery global, plugin formulir, datepicker, Highcharts, serta scanner tetap memakai urutan pemuatan aplikasi.
- Worker tetap `/sw.js` dengan scope `/`, manifest dan fallback `/offline.html` tetap tersedia. Uji service worker melalui HTTPS atau localhost.
- Konfigurasi manifest lama memakai origin produksi SUMO; identitas tersebut dipertahankan. Mengganti origin instalasi PWA merupakan perubahan konfigurasi terpisah.

## Scheduler dan queue

`autoapprove` terdaftar di `routes/console.php` dan berjalan setiap menit:

```cron
* * * * * cd /path/ke/web-gais && php artisan schedule:run >> /dev/null 2>&1
```

Untuk pengembangan gunakan `php artisan schedule:work`. Command hanya memperbarui problem report sesuai kondisi tanggal lama; pada MySQL batasnya memakai tanggal, bukan rolling 24 jam. Pertahankan koneksi queue yang sudah digunakan dan mulai ulang worker pada pergantian rilis.

## Pengujian

```bash
composer validate --strict
composer install
composer check-platform-reqs
composer audit
php artisan config:clear
php artisan test
npm ci
npm test
npm run build
npm audit
```

Suite standar memakai SQLite in-memory dan tidak membutuhkan Vite yang sedang berjalan. Suite mencakup login/token, middleware dan akses, approval, biaya/kuantitas, reminder/sewa, impor/ekspor XLSX, template, PDF/QR, dan unggahan gambar/PDF. `npm test` memakai test runner Node tanpa dependency tambahan untuk registrasi PWA dan perilaku offline worker.

Jalankan suite MySQL pada **database terisolasi yang boleh dikosongkan**. Suite ini menolak nama database yang tidak berakhiran `_test` atau `_testing`, tidak mengambil koneksi aplikasi dari `.env`, dan menggunakan `migrate:fresh`:

```bash
MYSQL_TEST_DATABASE=web_gais_test \
MYSQL_TEST_HOST=127.0.0.1 \
MYSQL_TEST_PORT=3306 \
MYSQL_TEST_USERNAME=root \
MYSQL_TEST_PASSWORD='test-password' \
php vendor/bin/phpunit -c phpunit.mysql.xml
```

Untuk Unix socket gunakan `MYSQL_TEST_SOCKET=/path/ke/mysql.sock`. Buat database tersebut terlebih dahulu dengan `utf8mb4_unicode_ci`. Suite MySQL menguji query dashboard, lookup impor tanpa membedakan kapitalisasi, approval, serta instalasi kosong dan upgrade schema lama berisi fixture, termasuk token yang tetap dapat dipakai. Tes menolak config cache agar koneksi pengujian tidak tertimpa konfigurasi aplikasi.

Periksa cache pada environment validasi sebelum rilis:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan route:list
php artisan schedule:list
php artisan config:clear
php artisan route:clear
```

Hasil validasi dan batas pemeriksaan browser dicatat di [laporan upgrade](docs/upgrade-validation.md). Cakupan CRUD tambahan, empat perbaikan bug, dan hasil tes regresi tersedia di [laporan CRUD](docs/crud-validation.md). Temuan bisnis lain yang tidak diubah tersedia di [catatan bisnis](docs/legacy-business-observations.md).

## Rollback

Masuk maintenance dan hentikan worker. Pulihkan rilis, lockfile, PHP runtime, dan aset build versi sebelumnya, pertahankan `APP_KEY` serta storage, lalu bersihkan/bangun kembali cache menggunakan runtime rilis tersebut. Kolom tambahan nullable `expires_at` boleh tetap ada ketika kode lama dipulihkan.

Jika benar-benar perlu menghapus kolom expiry, pastikan backup tersedia dan migration tersebut masih migration terakhir, lalu gunakan `php artisan migrate:rollback --step=1 --force` dengan kode Laravel 12 sebelum memulihkan kode lama. Penghapusan kolom menghilangkan metadata expiry; token dengan expiry yang sebelumnya aktif dapat kembali tidak dibatasi waktu. Pulihkan snapshot database hanya jika rollback memang memerlukan data lama dan perubahan setelah backup sudah diperhitungkan. Jangan gunakan reset/fresh sebagai rollback aplikasi.

## Referensi upgrade

- [Laravel 12 upgrade guide](https://laravel.com/docs/12.x/upgrade)
- [Laravel Excel 4 upgrade guide](https://docs.laravel-excel.com/4.x/getting-started/upgrade.html)
- [Laravel 12 frontend scaffold](https://raw.githubusercontent.com/laravel/laravel/12.x/package.json)
