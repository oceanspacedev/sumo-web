# Validasi frontend — 13 September 2026

Validasi memakai build produksi Vite dan HTTP lokal `http://127.0.0.1:8123`,
dengan database fixture terisolasi `web_gais_browser`. Tidak ada akun atau data
produksi yang dipakai. Fixture dibuat oleh skrip sementara yang memeriksa nama
database sebelum menjalankan migrasi dan membuat data sintetis.

## Build dan pemeriksaan otomatis

- Node.js 22.21.1 memenuhi minimum 22.12; `npm ci` berhasil dari lockfile.
- `npm run build` berhasil dengan Vite 7.3.6 dan menghasilkan empat entrypoint:
  aplikasi, registrasi PWA, pemindai permintaan, dan pemindai produk.
- `npm run watch` berhasil menjalankan build dan menunggu perubahan, lalu
  dihentikan setelah verifikasi. Skrip diperbaiki menjadi `vite build --watch`.
- `npm audit --json` selesai dengan **0 kerentanan**. Akses registry memerlukan
  izin jaringan karena percobaan awal di sandbox gagal resolusi DNS.
- `npm test`: **6 tes lulus**, menggunakan runner bawaan Node tanpa dependency
  tambahan. Tes menjalankan modul PWA dan kode worker sebenarnya dengan API
  browser/cache tiruan untuk memeriksa registrasi `/sw.js` dengan scope `/`,
  fallback navigasi offline, cache halaman awal, pembersihan cache lama milik
  aplikasi, permintaan online, pengecualian POST, serta kegagalan subresource
  tanpa mengirim HTML sebagai JavaScript.
- Semua entrypoint, `vite.config.js`, dan `public/sw.js` lulus pemeriksaan
  sintaks Node; diff frontend lulus `git diff --check`.

`@vite` sekarang dipanggil langsung. Build yang hilang tidak lagi diam-diam
menghapus seluruh JavaScript aplikasi dari HTML. Urutan vendor klasik tetap
dipertahankan sebelum modul aplikasi; jQuery, Select2, datepicker, daterangepicker,
Highcharts, dan Html5QrcodeScanner tetap berasal dari sumber yang sebelumnya
dipakai aplikasi. Inisialisasi tanggal jadwal dipindahkan dari layout ke
entrypoint aplikasi. Worker, manifest, dan halaman offline tetap dipertahankan.

## Pemeriksaan browser yang selesai

| Pemeriksaan | Hasil yang diamati |
| --- | --- |
| Halaman login | Form username/password dan tombol login tampil pada in-app browser serta Microsoft Edge. |
| Registrasi PWA | Console in-app browser mencatat `Service worker registered for scope: http://127.0.0.1:8123/` dari asset PWA hasil build. |
| Halaman `/productqr` | Pemindai tampil dengan tombol izin kamera, pilihan pemindaian berkas, serta field nama produk, satuan, harga, dan kategori. |
| Mode berkas QR | Memilih `Scan an Image File` menampilkan tombol pemilihan gambar dan area drop. Ini membuktikan library scanner dan modul Vite terinisialisasi. |
| Console halaman publik | Tidak ada error/warning yang tertangkap saat pemeriksaan login dan pemindai produk; log registrasi PWA tersedia. |
| Halaman `/scanqr` | Microsoft Edge menampilkan pemindai kamera/berkas serta field barang, kuantitas, pemohon, tanggal, status, dan catatan permintaan. |
| Login fixture admin | Setelah pengguna memberi izin eksplisit, login `upgrade-admin` berhasil dan mengarah ke `/dashboard`; navbar menampilkan `UPGRADE TEST ADMIN`. |
| Dashboard dan Highcharts | Screenshot dan accessibility tree menunjukkan layout Bootstrap serta kelima grafik: jumlah item permintaan, biaya permintaan, jumlah laporan, kategori laporan, dan biaya asuransi. Library melaporkan Highcharts 13.0.2 dari CDN lama; fixture belum memiliki seri transaksi. |
| Daterangepicker | Klik filter tanggal dashboard membuka kalender September/Oktober 2026, nilai rentang, serta tombol Apply/Cancel. Cancel menutup popup. |
| Datepicker bulan | Klik filter bulan asuransi membuka pemilih tahun 2026 dengan grid Jan–Dec. |
| Form produk dinamis | In-app browser: pilih tipe1, tambah dua baris, buka dan cari produk lewat Select2, isi kuantitas/alasan, hapus baris kedua; nilai baris pertama tetap ada. Mengganti tipe2 mengosongkan baris dan menyembunyikan upload sesuai perilaku lama. Tidak ada pengajuan yang disubmit. |
| Console form dan dashboard | Tidak ada error JavaScript yang tertangkap; Highcharts memberi warning nonfatal bahwa modul accessibility opsional belum dimuat, sesuai susunan CDN lama. |

## Batas validasi browser

Grafik sudah terinisialisasi dan terlihat, tetapi fixture browser belum memiliki seri transaksi. Query agregasi dengan data terisi diverifikasi terpisah melalui suite MySQL.

QR PNG berisi nilai sintetis `1` berhasil dibuat menggunakan Milon Barcode.
Namun proses file chooser browser tidak mengembalikan hasil sampai diinterupsi;
decode gambar dan hasil lookup produk **belum terverifikasi di browser**.
Sesudah interupsi, koneksi in-app browser sempat tidak tersedia. Fallback
Microsoft Edge digunakan. Login fixture sempat ditolak peninjauan izin otomatis
karena belum ada otorisasi eksplisit untuk akun tersebut; pengguna kemudian
memberi izin login akun uji lokal dan login berhasil. Mac juga sempat terkunci,
kemudian tersedia kembali sehingga pemeriksaan publik dan dashboard dilanjutkan.
Agen utama kemudian memakai koneksi in-app browser yang tersedia, login ke akun uji yang sudah diizinkan, dan menuntaskan pemeriksaan form dinamis. Hambatan izin dan Mac terkunci sudah tidak menahan pekerjaan. Server HTTP dan MySQL pengujian dihentikan setelah validasi selesai.

Izin kamera tidak diminta atau diubah. Fallback offline telah diuji pada runner
Node, tetapi pergantian jaringan online/offline secara nyata di browser belum
diuji. Manifest lama masih memakai origin SUMO absolut dan `scope` berbentuk
array; isi tersebut dipertahankan sesuai batas perubahan, dan instalabilitas
PWA pada origin lain tidak diklaim oleh pengujian ini.
