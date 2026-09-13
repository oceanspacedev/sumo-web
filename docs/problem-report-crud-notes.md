# Pemeriksaan lifecycle laporan gangguan

Pemeriksaan HTTP pada SQLite terisolasi lulus: **12 tes, 110 assertion** melalui
`vendor/bin/phpunit tests/Feature/ProblemReportCrudTest.php` pada PHP 8.4.23.
Kasus yang sama tersedia untuk MySQL melalui
`tests/MySql/ProblemReportCrudTest.php` dan trait bersama
`tests/Concerns/ExercisesProblemReportCrud.php`.

## Jalur yang lulus

- Form laporan baru, penyimpanan JPEG, kode `PR{id}`, pemilik, timestamp,
  kategori, status awal, serta isi berkas pada disk `public`.
- Daftar laporan dan form edit status; jadwal tanggal disimpan tanpa mengubah
  status penyelesaian.
- Unggah foto pengerjaan PNG, lalu penggantian dengan JPEG ketika laporan
  ditutup; referensi DB dan byte berkas baru sesuai, foto awal tetap tersedia.
- Penutupan mencatat executor dan waktunya; konfirmasi pelapor mengubah
  `status_client` dan membuka kembali akses ke form laporan baru.
- Pembatalan mencatat executor, waktu, alasan, dan status client selesai.
- Kategori/foto wajib serta penolakan berkas yang tidak didukung; tidak ada
  baris atau berkas baru setelah input ditolak.
- Daftar standar admin, manager HCM divisi 6, manager divisi lain, dan pelapor;
  laporan soft-deleted tidak tampil. Pencarian nama/tanggal pelapor tetap
  dibatasi ke laporannya sendiri. Filter status admin sesuai pilihan.
- Filter status pelapor untuk nilai `0` maupun `1` hanya mengembalikan laporan
  milik pelapor dengan status terpilih; laporan pengguna lain dan yang
  soft-deleted tidak ikut tampil.
- Edit `PENDING`, `CLOSED`, dan `CANCELLED` tanpa unggahan baru mempertahankan
  referensi foto sebelum/sesudah dan byte kedua berkas. Field unggahan yang
  tidak dikirim, string kosong, dan `null` sama-sama ditangani.
- ID laporan yang tidak ada menghasilkan 404 pada kedua form edit dan kedua
  endpoint pembaruan.

Route aplikasi tidak menyediakan halaman `show` khusus atau operasi hapus
laporan gangguan. Detail dibaca pada daftar/form edit; method `destroy()` masih
kosong dan tidak didaftarkan sebagai route. Hasil ini hanya mencakup operasi
yang benar-benar tersedia.

## Dua temuan sudah diperbaiki

Kedua masalah sebelumnya direproduksi pada HTTP dengan SQLite `:memory:` dan
`Storage::fake('public')`. Kode penyebab sudah ada pada versi `HEAD` sebelum
upgrade. Setelah pengguna meminta perbaikan, `ProblemReportController` diubah
pada dua lokasi berikut.

1. **Cakupan filter status pelapor.** Sebelumnya
   `GET /problemReport?selectStatusAkhir=1` mengembalikan laporan selesai milik
   pengguna lain. Cabang filter status untuk pelapor sekarang menambahkan
   kondisi `user_id` yang sama dengan daftar normal dan pencarian lain.
   Cabang admin serta pembagian kategori manager tidak diubah.
2. **Retensi foto ketika hanya status yang diedit.** Sebelumnya
   `POST /problemReport/{id}/updateStatus` tanpa `photo_after` mengosongkan
   referensi foto. Sekarang referensi itu hanya diganti ketika ada unggahan
   baru. Perubahan status, waktu penutupan, executor, hasil pekerjaan, dan
   jadwal tetap mengikuti alur sebelumnya. Validasi dan helper kompresi
   unggahan baru tetap digunakan.

Lima kasus regresi baru terlebih dahulu dijalankan terhadap kode yang belum
diperbaiki: dua kasus filter menghasilkan ID pengguna lain, dan tiga kasus
tanpa unggahan mengubah foto menjadi `null`. Kelimanya gagal sesuai temuan.
Setelah perbaikan, keseluruhan **12 tes / 110 assertion lulus**, termasuk
penggantian foto PNG menjadi JPEG pada lifecycle yang sudah ada. Tes regresi
tersimpan permanen pada trait bersama untuk dijalankan di SQLite dan MySQL.
