# Temuan bisnis lama di luar upgrade

Catatan ini membandingkan implementasi dengan versi sebelum upgrade Laravel 12. Perilaku berikut tidak diperbaiki dalam upgrade karena akan mengubah aturan atau hasil bisnis yang sudah berjalan.

## Status pembayaran kontrak sewa awal

Pada `resources/views/rents/rent/show.blade.php`, bagian ringkasan kontrak awal menghitung **Total Tagihan** dan **Sisa Tagihan** dengan pembulatan ke atas `ceil(hari / 366)`, tetapi **Status Tagihan** memakai `hari / 366` tanpa pembulatan. Contohnya, kontrak 367 hari dengan sewa Rp12.000.000 per tahun dan pembayaran Rp24.000.000 menampilkan sisa Rp0, tetapi status ringkasan menjadi `LEBIH`. Baris pada daftar sewa dan baris pembaruan kontrak memakai pembulatan untuk status sehingga dapat menampilkan `LUNAS` untuk nilai yang sama. Ketidakkonsistenan ini sudah ada sebelum perubahan Carbon.

## Pemohon pada batas permintaan terbuka

`RequestController::store()` menghitung batas dua permintaan berdasarkan pengguna yang login (`Auth::user()->id`), sedangkan pemohon yang disimpan berasal dari `user_id` dalam request. Pada pengajuan untuk pemohon yang berbeda, batas yang diperiksa mengikuti akun pembuat, bukan pemohon tersimpan. Kedua jalur penyimpanan sudah memakai pola ini sebelum upgrade. Perubahan kepemilikan atau aturan otorisasi membutuhkan pekerjaan bisnis tersendiri.

## Batas tanggal autoapproval yang dipertahankan

`autoapprove` memakai `DATE(closed_at) < (sekarang - 24 jam)`, bukan perbandingan langsung timestamp `closed_at`. Pengujian MySQL membuktikan bahwa pada pukul 00:00 hanya tanggal sebelum kemarin yang memenuhi syarat; pada pukul 00:01 semua laporan bertanggal kemarin dapat memenuhi syarat, termasuk laporan yang ditutup pukul 23:59. Syarat `closed_by` terisi dan `status_client = 0` tetap berlaku. Job tetap dijadwalkan setiap menit dan hanya mengubah tabel `problem_report`.
