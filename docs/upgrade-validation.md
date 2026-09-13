# Validasi upgrade Laravel 12

Dijalankan pada 13 September 2026 di workspace lokal, tanpa deployment produksi atau perubahan database aplikasi. Server HTTP dan MySQL sementara dihentikan setelah validasi. Instance MySQL untuk validasi dibuat baru di direktori sementara dan memakai Unix socket terpisah; seluruh datanya berupa fixture sintetis.

## Runtime dan dependensi

- PHP 8.4.23; Composer 2.10.2; Laravel 12.69.2.
- Node 22.21.1; Vite 7.3.6; Laravel Vite Plugin 2.x.
- SQLite in-memory untuk tes umum; MySQL 9.6.0 dengan konfigurasi charset/collation/strict proyek untuk tes integrasi.
- Sanctum 4.3.3, Excel 4.0.2, Intervention Image Laravel 4.1.1, DomPDF 3.1.2, Milon Barcode 13.5, Sentry Laravel 4.27.0.
- PHPUnit 11.5.56, Collision 8.9.5, Spatie Ignition 2.12.0, Fruitcake Debugbar 4.4.3.
- `composer validate --strict`, instalasi dari lockfile beserta package discovery, dan `composer check-platform-reqs` berhasil. Persyaratan platform tidak diabaikan.
- Instalasi bersih tambahan: 142 paket diekstrak dari lockfile ke direktori vendor kosong; seluruh versi cocok dengan lockfile, 25 persyaratan platform lulus. Instalasi terpisah memakai `--no-scripts`; script package discovery diverifikasi melalui instalasi workspace biasa.
- Audit Composer saat update: tidak ada advisory kerentanan. `npm audit`: 0 kerentanan.
- `npm ci`, `npm run build`, dan startup `npm run watch` berhasil. Build menghasilkan empat entrypoint dan manifest produksi.

## Hasil pengujian

| Pemeriksaan | Hasil |
| --- | --- |
| Suite SQLite | 98 tes, 1.547 assertion |
| Suite MySQL lengkap | 58 tes, 1.051 assertion |
| Suite Node/PWA | 6 tes |
| Lint PHP | Semua file aplikasi, bootstrap, konfigurasi, migration, routes, tes, Artisan, dan front controller lulus |
| Cache konfigurasi/routes/views | Berhasil; cache config/routes validasi disimpan terpisah lalu dibersihkan |
| Bootstrap HTTP/Artisan | Berhasil; enam endpoint API tetap memakai prefix `/api` |
| Scheduler | `autoapprove` terdaftar setiap menit |
| Whitespace diff | `git diff --check` lulus |

Setelah pemeriksaan CRUD tambahan, perbaikan format tanggal Carbon 3 pada daftar barang, dan perbaikan empat temuan CRUD yang diminta pengguna, suite lengkap dijalankan ulang: 98 tes SQLite dan 58 tes MySQL lulus. Cakupan, perbaikan, dan tes regresi tercatat di [laporan CRUD](crud-validation.md).

## Perilaku yang diverifikasi

- Isolasi SQLite juga diverifikasi dengan `DATABASE_URL` MySQL palsu yang diwariskan dari shell; tes tetap berjalan di SQLite in-memory.
- Login/logout web dan redirect, login API beserta envelope JSON lama, token baru dan token lama tanpa expiry, expiry yang valid/kedaluwarsa, akses role/divisi dan pembaca asuransi khusus, CSRF nyata di luar bypass PHPUnit, CORS, header proxy tidak tepercaya, serta request ke-61 yang ditolak limiter.
- Batas dua permintaan terbuka untuk tiga tipe dan status terbuka; empat tahap approval; kuantitas `NULL`, nol, dan pecahan; total biaya; pembatalan; catatan pemohon; stok dan nilai biaya tersimpan yang tetap.
- Nominal sewa awal/pembaruan di daftar dan detail, termasuk tahun kabisat dan urutan tanggal terbalik. Reminder asuransi awal/pembaruan diperiksa melalui hasil render pada batas 14/30 hari dengan jam selain tengah malam.
- Autoapproval hanya mengubah problem report yang memenuhi syarat. Tes MySQL mencakup batas pukul 00:00 dan 00:01, mempertahankan perbandingan tanggal lama.
- Enam template XLSX beserta heading; keenam keluarga impor insert/update/skip, validasi parent/tanggal/lookup/file, transaksi rollback, mapping angka/tanggal/relasi pada ekspor, filter laporan, dan ekspor QR beberapa baris. Seluruh enam kelas impor juga diuji dengan kapitalisasi berbeda pada MySQL.
- Empat helper penyimpanan gambar mempertahankan nama/path, ambang kompresi **lebih dari 2 MB**, JPEG kualitas 30 dengan GD, encoding PNG, orientasi EXIF seperti implementasi lama, passthrough PDF, dan batas validasi upload yang sudah ada.
- Kedua route PDF menghasilkan dokumen PDF; renderer QR menghasilkan PNG dan HTML.
- Instalasi baru diuji memakai migration dan semua seeder historis, login akun admin fixture, serta render dashboard.
- Migration dijalankan dari database kosong, lalu diulang pada schema historis berisi pengguna, produk, request/detail, dan token. Nilai bisnis dan token dibandingkan sebelum/sesudah; token lama tetap dapat mengakses API. Rollback hanya migration tambahan mempertahankan data bisnis dan hash token. Kolom expiry yang sudah ada juga dipertahankan dan index yang hilang ditambahkan.
- Tes Node menjalankan modul PWA dan worker sebenarnya untuk registrasi `/sw.js` berscope `/`, cache home/fallback navigasi, pengabaian POST, prioritas jaringan, dan pembersihan hanya cache aplikasi sendiri.

## Pemeriksaan browser

Login akun sintetis lokal berhasil setelah izin pengguna diberikan. Kelima grafik Highcharts tampil; filter rentang tanggal dan pemilih bulan dapat dibuka. Form dinamis dapat menambah/menghapus baris, mencari produk via Select2, mempertahankan input kuantitas/alasan, dan memperbarui field saat tipe pengajuan berubah. Tidak ada pengajuan yang disubmit melalui browser. Halaman kedua scanner QR terinisialisasi, dan console mengonfirmasi service worker `/sw.js` dengan scope `/`. Tidak ada error JavaScript yang tertangkap; warning modul accessibility Highcharts lama dicatat.

Pemindaian fixture melalui dialog unggah mengalami timeout pada alat browser; decode/lookup QR dan pergantian jaringan offline langsung di browser belum diklaim lulus. Renderer QR, route pencarian/ekspor terkait, dan fallback worker diuji secara otomatis. Detail ada di [laporan browser](frontend-browser-validation.md).

## Batas validasi

Pengujian ini memakai schema historis dari migration repository dan fixture sintetis, bukan salinan database produksi. Server MySQL versi lain, data produksi, integrasi pengiriman Sentry, dan perangkat kamera fisik membutuhkan validasi pada lingkungan terkait. Pengiriman Sentry dimatikan selama tes; jalur pelaporan tunggal diverifikasi dari konfigurasi bootstrap.

Perilaku bisnis lama yang sengaja dipertahankan tercatat di [catatan bisnis](legacy-business-observations.md). Langkah instalasi, upgrade database, scheduler, dan rollback ada di [README](../README.md).
