@extends('layouts.master')

@section('content')
<div class="main">
    <div class="main-content">
        <div class="container-fluid">
            <!-- Flash Message -->
            @if (session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <i class="fa fa-check-circle"></i> {{ session('success') }}
            </div>
            @endif
            @if (session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <i class="fa fa-times-circle"></i> {{ session('error') }}
            </div>
            @endif

            <div class="row">
                <!-- FORM PENGATURAN NOTIFIKASI -->
                <div class="col-md-7">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-whatsapp text-success"></i> Pengaturan Notifikasi WhatsApp (WagHub)</h3>
                            <p class="panel-subtitle">Atur modul apa saja dan kapan notifikasi pengingat jatuh tempo dikirimkan secara otomatis.</p>
                        </div>
                        <div class="panel-body">
                            <form action="/notification-settings/update" method="POST">
                                @csrf
                                
                                <!-- STATUS GLOBAL -->
                                <div class="form-group" style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef;">
                                    <label class="fancy-checkbox" style="margin-bottom: 0;">
                                        <input type="checkbox" name="is_enabled" value="1" {{ $setting->is_enabled ? 'checked' : '' }}>
                                        <span><strong style="font-size: 15px;">Aktifkan Pengingat Otomatis WhatsApp</strong></span>
                                    </label>
                                    <small class="text-muted" style="display: block; margin-top: 5px;">Jika dinonaktifkan, cron scheduler tidak akan mengirimkan pesan apa pun.</small>
                                </div>

                                <!-- NOMOR WA TUJUAN -->
                                <div class="form-group">
                                    <label for="target_phones">Nomor WhatsApp Tujuan (Penerima Notifikasi)</label>
                                    <input type="text" name="target_phones" id="target_phones" class="form-control" value="{{ $setting->target_phones }}" placeholder="Contoh: 081234567890, 089876543210">
                                    <small class="text-muted">Pisahkan dengan tanda koma jika ingin mengirim ke lebih dari satu nomor. Nomor ini dipakai untuk cutoff pengajuan, dan sebagai cadangan jika data sewa atau asuransi tidak mengisi nomor sendiri.</small>
                                </div>

                                <!-- JAM PENGIRIMAN -->
                                <div class="form-group">
                                    <label for="send_time">Jam Pengiriman Rutin Harian (WIB)</label>
                                    <input type="time" name="send_time" id="send_time" class="form-control" style="max-width: 200px;" value="{{ $setting->send_time }}">
                                    <small class="text-muted">Rekomendasi: jam 08:00 atau 08:30 WIB setiap pagi hari kerja.</small>
                                </div>

                                <hr>
                                <h4 style="font-weight: 600; color: #333;"><i class="fa fa-sliders"></i> Pengaturan Modul & Hari Peringatan (H-)</h4>
                                <br>

                                <!-- MODUL SEWA -->
                                <div class="well" style="background: #ffffff; border-left: 4px solid #41B314;">
                                    <label class="fancy-checkbox">
                                        <input type="checkbox" name="remind_rent" value="1" {{ $setting->remind_rent ? 'checked' : '' }}>
                                        <span><strong>1. Perjanjian Sewa (Rents)</strong></span>
                                    </label>
                                    <p class="text-muted" style="font-size: 12px; margin-bottom: 8px;">Mengirim notifikasi sebelum tanggal berakhir kontrak sewa tempat/gedung.</p>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <label style="font-size: 12px;">Kirim Pengingat pada Hari (H-):</label>
                                            <input type="text" name="rent_days_before" class="form-control input-sm" value="{{ $setting->rent_days_before }}" placeholder="30,14,7,1">
                                            <small class="text-muted">Masukkan hari sebelum jatuh tempo dipisah koma (contoh: 30,14,7,1).</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- MODUL ASURANSI -->
                                <div class="well" style="background: #ffffff; border-left: 4px solid #00AAFF;">
                                    <label class="fancy-checkbox">
                                        <input type="checkbox" name="remind_insurance" value="1" {{ $setting->remind_insurance ? 'checked' : '' }}>
                                        <span><strong>2. Polis Asuransi (Insurances)</strong></span>
                                    </label>
                                    <p class="text-muted" style="font-size: 12px; margin-bottom: 8px;">Mengirim notifikasi sebelum polis asuransi stok gudang / bangunan kedaluwarsa.</p>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <label style="font-size: 12px;">Kirim Pengingat pada Hari (H-):</label>
                                            <input type="text" name="insurance_days_before" class="form-control input-sm" value="{{ $setting->insurance_days_before }}" placeholder="30,14,7,1">
                                            <small class="text-muted">Masukkan hari sebelum jatuh tempo dipisah koma (contoh: 30,14,7,1).</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- MODUL CUTOFF PENGAJUAN -->
                                <div class="well" style="background: #ffffff; border-left: 4px solid #e74c3c;">
                                    <label class="fancy-checkbox">
                                        <input type="checkbox" name="remind_request_cutoff" value="1" {{ $setting->remind_request_cutoff ? 'checked' : '' }}>
                                        <span><strong>3. Batas Akhir (Cutoff) Pengajuan Barang</strong></span>
                                    </label>
                                    <p class="text-muted" style="font-size: 12px; margin-bottom: 8px;">Mengingatkan bahwa periode pengajuan barang rutin akan segera ditutup.</p>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <label style="font-size: 12px;">Kirim Pengingat pada Hari (H-):</label>
                                            <input type="text" name="request_cutoff_days_before" class="form-control input-sm" value="{{ $setting->request_cutoff_days_before }}" placeholder="3,1">
                                            <small class="text-muted">Hari sebelum tanggal penutupan (contoh: 3,1).</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right" style="margin-top: 20px;">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> SIMPAN PENGATURAN</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TEST KIRIM WA & INFO STATUS GATEWAY -->
                <div class="col-md-5">
                    <!-- TEST KIRIM -->
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-paper-plane text-primary"></i> Uji Coba Kirim Pesan (Test Send)</h3>
                        </div>
                        <div class="panel-body">
                            <p class="text-muted">Gunakan form ini untuk mengetes apakah nomor WhatsApp Anda dapat menerima pesan langsung dari WagHub API.</p>
                            <form action="/notification-settings/test" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="test_phone">Nomor WhatsApp Tujuan Tes</label>
                                    <input type="text" name="test_phone" id="test_phone" class="form-control" placeholder="Contoh: 081234567890" required>
                                </div>
                                <button type="submit" class="btn btn-success btn-block"><i class="fa fa-whatsapp"></i> KIRIM TEST PESAN SEKARANG</button>
                            </form>
                        </div>
                    </div>

                    <!-- KREDENSIAL INFO -->
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-info-circle text-info"></i> Status Koneksi WagHub</h3>
                        </div>
                        <div class="panel-body">
                            <ul class="list-unstyled" style="font-size: 13px;">
                                <li style="margin-bottom: 8px;"><strong>Gateway URL:</strong> <code>https://waghub.mekayastudio.com</code></li>
                                <li style="margin-bottom: 8px;"><strong>Status Token:</strong> <span class="badge bg-success" style="background-color: #5cb85c;">Terhubung</span></li>
                                <li style="margin-bottom: 8px;"><strong>Command Scheduler:</strong> <code>php artisan reminder:whatsapp</code></li>
                            </ul>
                            <div class="alert alert-info" style="margin-bottom: 0; font-size: 12px;">
                                <i class="fa fa-lightbulb-o"></i> <strong>Anti-Spam Aktif:</strong> Sistem memiliki perlindungan Idempotency-Key dan Log Harian, sehingga satu data deadline tidak akan terkirim ganda ke nomor yang sama dalam 1 hari.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-exchange text-warning"></i> Nomor Pengingat per Data</h3>
                            <p class="panel-subtitle">Ganti atau hapus satu nomor pada banyak data sewa dan asuransi sekaligus.</p>
                        </div>
                        <div class="panel-body">
                            <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#bulkPhoneModal">
                                <i class="fa fa-exchange"></i> Ganti Nomor Secara Masal
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="bulkPhoneModal" role="dialog" aria-labelledby="bulkPhoneModalLabel">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                            <h1 class="modal-title" id="bulkPhoneModalLabel">Ganti Nomor Secara Masal</h1>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="reminderPhoneSearch">Cari nomor atau nama data</label>
                                <input type="text" id="reminderPhoneSearch" class="form-control" placeholder="Cari nomor atau nama data">
                            </div>
                            <div id="bulkPhoneList" style="max-height: 60vh; overflow-y: auto;">
                                @forelse ($phoneGroups as $group)
                                    <div class="well phone-group" style="background: #fff;">
                                        <h4 style="margin-top: 0;">
                                            <i class="fa fa-whatsapp text-success"></i> {{ $group['phone'] }}
                                            <small class="text-muted">{{ $group['rent_count'] }} perjanjian sewa, {{ $group['insurance_count'] }} polis asuransi</small>
                                        </h4>
                                        <ul style="margin-bottom: 12px;">
                                            @foreach ($group['records'] as $record)
                                                <li><span class="label {{ $record['type'] === 'SEWA' ? 'label-success' : 'label-info' }}">{{ $record['type'] }}</span> {{ $record['label'] }}</li>
                                            @endforeach
                                        </ul>
                                        <form action="/notification-settings/replace-phones" method="POST" class="form-inline">
                                            @csrf
                                            <input type="hidden" name="old_phone" value="{{ $group['phone'] }}">
                                            <div class="form-group" style="margin-right: 8px; margin-bottom: 8px;">
                                                <input type="text" name="new_phone" class="form-control" placeholder="Nomor baru">
                                            </div>
                                            <button type="submit" name="action" value="replace" class="btn btn-warning" style="margin-bottom: 8px;"><i class="fa fa-exchange"></i> GANTI</button>
                                            <button type="submit" name="action" value="delete" class="btn btn-danger" style="margin-bottom: 8px;" onclick="return confirm('Hapus nomor {{ $group['phone'] }} dari semua data di atas?');"><i class="fa fa-trash"></i> HAPUS</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-muted" style="margin-bottom: 0;">Belum ada data sewa atau asuransi yang mengisi nomor pengingat.</p>
                                @endforelse
                                <p id="bulkPhoneNoMatch" class="text-muted" style="display: none; margin-bottom: 0;">Tidak ada nomor yang cocok dengan pencarian.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LOG RIWAYAT PENGIRIMAN -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-history"></i> Riwayat Pengiriman Notifikasi WhatsApp (Terbaru)</h3>
                        </div>
                        <div class="panel-body table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Tipe Notifikasi</th>
                                        <th>Tujuan</th>
                                        <th>Status</th>
                                        <th>Pratinjau Pesan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($logs as $log)
                                    <tr>
                                        <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if ($log->notif_type === 'RENT')
                                                <span class="label label-success">SEWA</span>
                                            @elseif ($log->notif_type === 'INSURANCE')
                                                <span class="label label-info">ASURANSI</span>
                                            @elseif ($log->notif_type === 'REQUEST_SETTING')
                                                <span class="label label-warning">PENGAJUAN</span>
                                            @else
                                                <span class="label label-default">{{ $log->notif_type }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $log->phone }}</td>
                                        <td>
                                            @if ($log->status === 'SUCCESS')
                                                <span class="badge" style="background-color: #5cb85c;">BERHASIL</span>
                                            @else
                                                <span class="badge" style="background-color: #d9534f;">GAGAL</span>
                                            @endif
                                        </td>
                                        <td><small style="color: #555;">{{ $log->message_preview }}</small></td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada riwayat pengiriman notifikasi.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="pull-right">
                                {{ $logs->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('footer')
<script>
    (function () {
        var input = document.getElementById('reminderPhoneSearch');
        var list = document.getElementById('bulkPhoneList');
        var empty = document.getElementById('bulkPhoneNoMatch');
        if (!input || !list) {
            return;
        }

        input.addEventListener('input', function () {
            var query = input.value.toLowerCase().trim();
            var groups = list.getElementsByClassName('phone-group');
            var visible = 0;

            for (var i = 0; i < groups.length; i++) {
                var matched = groups[i].textContent.toLowerCase().indexOf(query) !== -1;
                groups[i].style.display = matched ? '' : 'none';
                if (matched) {
                    visible++;
                }
            }

            if (empty) {
                empty.style.display = groups.length > 0 && visible === 0 ? '' : 'none';
            }
        });
    })();
</script>
@endsection
