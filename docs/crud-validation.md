# Pemeriksaan CRUD setelah upgrade

Pengujian tambahan dilakukan pada 13 September 2026 melalui endpoint HTTP dengan database dan storage terisolasi. Tidak ada data aplikasi yang diubah.

## Hasil alur normal

| Modul | Operasi yang diverifikasi |
| --- | --- |
| Area, badan usaha, kategori barang, divisi, role, tipe unit, tipe pengajuan, kategori gangguan, kategori/provider/scope asuransi | List, create, edit, update, soft delete; divisi juga restore |
| Pengaturan pengajuan | List, create, edit, update; tidak ada route delete |
| Pengguna | Create dengan/tanpa foto, penolakan foto tidak valid tanpa row parsial, kegagalan storage/database, list/search/profile/edit, update password dan foto, soft delete, restore |
| Barang | Create dengan/tanpa foto, list/search/edit, update angka nol/foto, soft delete dan penghapusan foto aktif, lookup produk |
| API tipe unit | Create, read, update, delete dengan bearer token Sanctum dan envelope JSON lama |
| Asuransi dan pembaruan | Create, list/detail/edit, update, soft delete; tanggal, nominal nol, relasi induk dan nominal induk tetap |
| Sewa dan pembaruan | Create, list/detail/edit, update, soft delete; tanggal, nominal nol, bukti pembayaran dan relasi induk |
| Laporan gangguan | List/create/upload, penjadwalan, edit/update status, penggantian foto, penutupan, konfirmasi pelapor, pembatalan, filter normal dan scope role; tidak ada route show/delete |

Suite CRUD tambahan yang sama dijalankan pada SQLite dan MySQL. Tes memeriksa perubahan database dan file, binding route, hasil render, serta flash success/error; redirect saja tidak dianggap bukti penyimpanan berhasil.

- Suite SQLite lengkap: **98 tes, 1.547 assertion lulus**.
- Suite MySQL lengkap: **58 tes, 1.051 assertion lulus**.
- Kompilasi seluruh Blade melalui `php artisan view:cache` dan `git diff --check` lulus.

## Kegagalan upgrade yang diperbaiki

Daftar barang memanggil `formatLocalized('%A, %d %b %Y')`, API yang sudah dihapus pada Carbon 3. Saat ada barang dengan timestamp update, halaman menghasilkan HTTP 500. Pemanggilan diganti menjadi `format('l, d M Y')` dengan gaya tanggal yang sama, misalnya `Sunday, 13 Sep 2026`. Tes CRUD barang memverifikasi tanggal ini dan status HTTP 200 di kedua database.

## Empat temuan CRUD sudah diperbaiki

Keempat masalah berikut awalnya dicatat sebagai temuan sebelum upgrade. Setelah pengguna meminta perbaikan, semuanya diperbaiki dan dilindungi tes regresi yang sama pada SQLite dan MySQL.

1. **Tambah pengguna tanpa foto:** foto sekarang opsional sesuai form dan schema; pembuatan berhasil dengan avatar bawaan, hash password, dan remember token. Foto divalidasi/disimpan sebelum satu operasi insert user, sehingga foto yang tidak valid, melebihi 2 MB, atau gagal ditulis tidak meninggalkan user parsial. Jika insert database gagal, unggahan baru dibersihkan. Nama file yang sudah dipakai mendapat suffix tambahan agar tidak menimpa foto yang ada. Tes karakterisasi lama diganti dengan assertion hasil yang benar.
2. **Filter status laporan untuk end-user:** cabang filter sekarang membatasi `user_id` ke pelapor yang login. Tes untuk status `0` dan `1` memastikan laporan pengguna lain, status lain, dan laporan soft-deleted tidak ikut tampil. Akses admin serta kategori manager tetap mengikuti aturan sebelumnya.
3. **Ubah status laporan tanpa foto baru:** `photo_after` hanya diganti jika unggahan baru diberikan. Tes perubahan ke `PENDING`, `CLOSED`, dan `CANCELLED` mempertahankan referensi serta byte foto lama ketika field tidak dikirim, kosong, atau `null`. Penggantian foto baru juga tetap lulus. Detail ada di [catatan laporan gangguan](problem-report-crud-notes.md).
4. **Tanggal form edit pengaturan pengajuan:** masing-masing input sekarang memiliki satu atribut `value`; bulan memakai `m-Y` sesuai picker/controller, sementara tanggal buka/tutup tetap `d/m/Y`. Tes membaca HTML hasil render, memeriksa nilai awal, lalu mengirim ulang nilai form tanpa perubahan dan membuktikan tanggal di database tetap sama. Pemeriksaan ini melalui render HTTP dan parsing DOM, bukan klaim uji interaksi browser baru.

Regresi utama dijalankan sebelum perbaikan dan gagal sesuai temuan, lalu lulus setelah kode diperbaiki. Seluruh suite dijalankan ulang untuk memastikan alur CRUD lain tetap berjalan. Tidak ada deployment, perubahan schema, atau koreksi otomatis terhadap data lama; pengujian memakai fixture terisolasi. Temuan bisnis lain di luar empat masalah ini tetap tercatat di [catatan bisnis](legacy-business-observations.md).
