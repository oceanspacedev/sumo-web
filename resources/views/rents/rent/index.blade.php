@extends('layouts.master')

@section('content')
    <div class="main">
        <div class="main-content">
            <div class="container-fluid">
                @if (session('success'))
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <i class="fa fa-check-circle"></i> {{session('success')}}
                </div>
                @endif
                @if (session('error'))
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <i class="fa fa-check-circle"></i> {{session('error')}}
                </div>
                @endif
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading">
                                <div class="col-md-12">
                                    <div class="col-md-12" style="margin-bottom: 20px;">
                                        <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="/dashboard">Beranda</a></li>
                                            <li class="breadcrumb-item active" aria-current="page">Perjanjian Sewa</li>
                                        </ol>
                                        </nav>
                                    </div>
                                    <div class="col-md-12" style="margin-bottom: 30px;">
                                        <div class="col-md-5 text-center">
                                        <form class="form-inline" id="search_form" action="/rent">
                                            <div class="form-group">
                                            <input type="text" class="form-control" name="search" placeholder="Cari ..." style="width: 140px;">
                                            <a href="javascript:{}" onclick="document.getElementById('search_form').submit();" class="btn btn-info" ><span class="lnr lnr-magnifier"></span></a>
                                            </div>
                                        </form>
                                        </div>
                                        <div class="col-md-3 text-right">
                                            <form class="form-inline" id="inputStatus" action="/rent">
                                                <div class="form-group">
                                                <select class="form-control" name="selectStatus">
                                                    <option selected value="">-- Status --</option>
                                                    <option value="BERJALAN">BERJALAN</option>
                                                    <!-- <option value="PEMBAHARUAN">PEMBAHARUAN</option> -->
                                                    <option value="TUTUP">TUTUP</option>
                                                    <option value="REFUND">REFUND</option>
                                                </select>
                                                <a href="javascript:{}" onclick="document.getElementById('inputStatus').submit();" class="btn btn-info" ><span class="lnr lnr-magnifier"></span></a>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <a href="/rent/create" class="btn btn-info" data-toggle="modal" data-target="#addRentsModal" data-toggle="tooltip" data-placement="top" title="Tambah data baru"><span class="lnr lnr-plus-circle"></span></a>
                                            <a href="/rent/export" class="btn btn-primary" data-toggle="tooltip" data-placement="bottom" title="Export Perjanjian Sewa"><span class="lnr lnr-download"></span></a>
                                            <a class="btn btn-success" data-toggle="modal" data-target=".importModal" data-toggle="tooltip" data-placement="top" title="Import Perjanjian Sewa"><span class="lnr lnr-upload"></span></a>
                                            <a href="/rent/export/template" class="btn btn-default" data-toggle="tooltip" data-placement="top" title="Download template"><span class="lnr lnr-text-align-justify"></span></a>
                                        </div>
                                        <br>
                                    </div>
                                </div>
                            </div>
                            <br><br><br>
                            <div class="panel-body table-responsive">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                        <th>Aksi</th>
                                        <th>NO</th>
                                        <th>KODE</th>
                                        <th>Nama Toko</th>
                                        <th>Alamat</th>
                                        <th>Pihak Pertama</th>
                                        <th>Pihak Kedua</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Tanggal Akhir</th>
                                        <th>Reminder</th>
                                        <th>Tanggal Reminder</th>
                                        <th>Sewa pertahun</th>
                                        <th>Total Tagihan</th>
                                        <th>Dana PKP</th>
                                        <th>Dana NON-PKP</th>
                                        <th>Sisa Tagihan</th>
                                        <th>Status Tagihan</th>
                                        <th>BukPot</th>
                                        <th>Berkas</th>
                                        <th>Bukti Bayar</th>
                                        <th>Status</th>
                                        <th>Catatan</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php
                                        $sortedRents = $rents->sortBy(function ($rent) {
                                            $expired_date = $rent->rent_update->isNotEmpty() ? $rent->rent_update->first()->expired_date : $rent->expired_date;
                                            return Carbon\Carbon::parse($expired_date);
                                        });
                                    @endphp

                                    @foreach ($sortedRents as $rent)
                                    @php
                                        if ($rent->rent_update->isNotEmpty()) {
                                            $expiredDate = Carbon\Carbon::parse($rent->rent_update->first()->expired_date);
                                            $reminderDate = Carbon\Carbon::parse($rent->rent_update->first()->expired_date)->subMonths($rent->rent_update->first()->month_before_reminder)->format('d M Y');
                                        } else {
                                            $expiredDate = Carbon\Carbon::parse($rent->expired_date);
                                            $reminderDate = Carbon\Carbon::parse($rent->expired_date)->subMonths($rent->month_before_reminder)->format('d M Y');
                                        }

                                        $diffInDays = Carbon\Carbon::now()->diffInDays($expiredDate, false);

                                        $rowStyle = ''; // initialize the variable

                                        if ((Carbon\Carbon::now() >= Carbon\Carbon::parse($reminderDate)->subDays(30)) && (Carbon\Carbon::now() < Carbon\Carbon::parse($reminderDate))) {
                                            $rowStyle = 'background-color: #fcf8e3; color: #8a6d3b;';
                                        } elseif (Carbon\Carbon::now() >= Carbon\Carbon::parse($reminderDate)) {
                                            if ($rent->rent_update->isNotEmpty()) {
                                                $status = $rent->rent_update->first()->status;
                                                
                                                if ($status === 'BERJALAN' || $status === 'PEMBAHARUAN') {
                                                    $rowStyle = 'background-color: #f2dede; color: #b96564;';
                                                } else {
                                                    $rowStyle = 'background-color: #EAECEA; color: #000000;';
                                                }
                                            } else {
                                                $status = $rent->status;

                                                if ($status === 'BERJALAN' || $status === 'PEMBAHARUAN') {
                                                    $rowStyle = 'background-color: #f2dede; color: #b96564;';
                                                } else {
                                                    $rowStyle = 'background-color: #EAECEA; color: #000000;';
                                                }
                                            }
                                        } else {
                                            $rowStyle = 'background-color: white;';
                                        }
                                    @endphp
                                    <tr style="{{$rowStyle}}">
                                        <td>
                                            <!-- <a href="/rent/{{$rent->id}}/edit" class="btn btn-warning btn-xs" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a> -->
                                            <!-- BUTTON DELETE -->
                                            <!-- <a href="/rent/{{$rent->id}}/delete" class="btn btn-danger btn-xs" onclick="return confirm('Yakin akan menghapus data ?')"><span class="lnr lnr-trash"></span></a> -->
                                            <a href="/rent/{{$rent->id}}" class="btn btn-default btn-xs" type="button"><span class="lnr lnr-eye"></span></a>
                                        </td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $rent->rent_update->isNotEmpty() ? $rent->rent_update->first()->rent_code : $rent->rent_code }}">
                                            @if ($rent->rent_update->isNotEmpty())
                                                {!! Str::limit($rent->rent_update->first()->rent_code, 12, '...') !!}
                                            @else
                                                {!! Str::limit($rent->rent_code, 12, '...') !!}
                                            @endif
                                        </td>
                                        <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $rent->rented_detail }}">{!! Str::limit($rent->rented_detail, 30, '...') !!}</td>
                                        <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $rent->rented_address }}">{!! Str::limit($rent->rented_address, 30, '...') !!}</td>
                                        <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $rent->first_party }}">{!! Str::limit($rent->first_party, 12, '...') !!}</td>
                                        <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $rent->second_party }}">{!! Str::limit($rent->second_party, 12, '...') !!}</td>
                                        <td><strong>
                                            @if ($rent->rent_update->isNotEmpty())
                                                {{ Carbon\Carbon::parse($rent->rent_update->first()->join_date)->format('d M Y') }}
                                            @else
                                                {{ Carbon\Carbon::parse($rent->join_date)->format('d M Y') }}
                                            @endif
                                            </strong>
                                        </td>
                                        <td><strong>
                                            @if ($rent->rent_update->isNotEmpty())
                                                {{ Carbon\Carbon::parse($rent->rent_update->first()->expired_date)->format('d M Y') }}
                                            @else
                                                {{ Carbon\Carbon::parse($rent->expired_date)->format('d M Y') }}
                                            @endif
                                            </strong>
                                        </td>
                                        <!-- BULAN REMINDER -->
                                        <td>
                                            @if ($rent->rent_update->isNotEmpty())
                                                {{ $rent->rent_update->first()->month_before_reminder}} bulan
                                            @else
                                                {{ $rent->month_before_reminder}} bulan
                                            @endif    
                                        </td>
                                        <!-- TANGGAL REMINDER -->
                                        <td><strong>
                                            @if ($rent->rent_update->isNotEmpty())
                                                {{ Carbon\Carbon::parse($rent->rent_update->first()->expired_date)->subMonths($rent->rent_update->first()->month_before_reminder)->format('d M Y') }}
                                            @else
                                                {{ Carbon\Carbon::parse($rent->expired_date)->subMonths($rent->month_before_reminder)->format('d M Y') }}
                                            @endif
                                        </strong></td>
                                        <td>
                                            @if ($rent->rent_update->isNotEmpty())
                                                Rp {{ number_format($rent->rent_update->first()->rent_per_year, 0, ',', '.') }}
                                                @else
                                                Rp {{ number_format($rent->rent_per_year, 0, ',', '.') }}
                                                @endif
                                        </td>
                                        <!-- TOTAL TAGIHAN -->
                                        <td><strong>
                                            @if ($rent->rent_update->isNotEmpty())
                                                Rp {{ number_format(
                                                    Carbon\Carbon::parse($rent->rent_update->first()->expired_date)->diffInDays($rent->rent_update->first()->join_date) / 366 == 1 
                                                    ? 1 * $rent->rent_update->first()->rent_per_year 
                                                    : ceil(Carbon\Carbon::parse($rent->rent_update->first()->expired_date)->diffInDays($rent->rent_update->first()->join_date) / 366) * $rent->rent_update->first()->rent_per_year, 0, ',', '.') 
                                            }}
                                            @else
                                                Rp {{ number_format(
                                                    Carbon\Carbon::parse($rent->expired_date)->diffInDays($rent->join_date) / 366 == 1 
                                                    ? 1 * $rent->rent_per_year 
                                                    : ceil(Carbon\Carbon::parse($rent->expired_date)->diffInDays($rent->join_date) / 366) * $rent->rent_per_year, 0, ',', '.')  
                                            }}
                                            @endif
                                        </strong></td>
                                        <td>
                                            @if ($rent->rent_update->isNotEmpty())
                                                Rp {{ number_format($rent->rent_update->first()->cvcs_fund, 0, ',', '.') }}
                                            @else
                                                Rp {{ number_format($rent->cvcs_fund, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($rent->rent_update->isNotEmpty())
                                                Rp {{ number_format($rent->rent_update->first()->online_fund, 0, ',', '.') }}
                                            @else
                                                Rp {{ number_format($rent->online_fund, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <!-- SISA TAGIHAN -->
                                        <td><strong>
                                            @if ($rent->rent_update->isNotEmpty())
                                                Rp {{ number_format(
                                                    Carbon\Carbon::parse($rent->rent_update->first()->expired_date)->diffInDays($rent->rent_update->first()->join_date) / 366 == 1 
                                                    ? (1 * $rent->rent_update->first()->rent_per_year) - ($rent->rent_update->first()->cvcs_fund + $rent->rent_update->first()->online_fund)
                                                    : (ceil(Carbon\Carbon::parse($rent->rent_update->first()->expired_date)->diffInDays($rent->rent_update->first()->join_date) / 366) * $rent->rent_update->first()->rent_per_year) - ($rent->rent_update->first()->cvcs_fund + $rent->rent_update->first()->online_fund), 0, ',', '.') 
                                            }}
                                            @else
                                                Rp {{ number_format(
                                                    Carbon\Carbon::parse($rent->expired_date)->diffInDays($rent->join_date) / 366 == 1 
                                                    ? (1 * $rent->rent_per_year) - ($rent->cvcs_fund + $rent->online_fund)
                                                    : (ceil(Carbon\Carbon::parse($rent->expired_date)->diffInDays($rent->join_date) / 366 ) * $rent->rent_per_year) - ($rent->cvcs_fund + $rent->online_fund), 0, ',', '.')  
                                            }}
                                            @endif
                                        </strong></td>
                                        <!-- STATUS TAGIHAN -->
                                        <td><strong>
                                            @if ($rent->rent_update->isNotEmpty())
                                                @php
                                                    $remainingPayment = (Carbon\Carbon::parse($rent->rent_update->first()->expired_date)->diffInDays($rent->rent_update->first()->join_date) / 366 == 1)
                                                    ? (1 * $rent->rent_update->first()->rent_per_year) - ($rent->rent_update->first()->cvcs_fund + $rent->rent_update->first()->online_fund)
                                                    : (ceil(Carbon\Carbon::parse($rent->rent_update->first()->expired_date)->diffInDays($rent->rent_update->first()->join_date) / 366) * $rent->rent_update->first()->rent_per_year) - ($rent->rent_update->first()->cvcs_fund + $rent->rent_update->first()->online_fund);

                                                    $status = $remainingPayment == 0 ? 'LUNAS' : ($remainingPayment < 0 ? 'LEBIH' : 'BELUM LUNAS');
                                                @endphp
                                                {{ $status }}
                                            @else
                                                @php
                                                    $remainingPayment = (Carbon\Carbon::parse($rent->expired_date)->diffInDays($rent->join_date) / 366 == 1)
                                                    ? (1 * $rent->rent_per_year) - ($rent->cvcs_fund + $rent->online_fund)
                                                    : (ceil(Carbon\Carbon::parse($rent->expired_date)->diffInDays($rent->join_date) / 366) * $rent->rent_per_year) - ($rent->cvcs_fund + $rent->online_fund);

                                                    $status = $remainingPayment == 0 ? 'LUNAS' : ($remainingPayment < 0 ? 'LEBIH' : 'BELUM LUNAS');
                                                @endphp
                                                {{ $status }}
                                            @endif
                                        </strong></td>
                                        <td>
                                            @if ($rent->rent_update->isNotEmpty())
                                                {{ $rent->rent_update->first()->deduction_evidence }}
                                            @else
                                                {{ $rent->deduction_evidence }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($rent->rent_update->isNotEmpty())
                                                {{ $rent->rent_update->first()->document }}
                                            @else
                                                {{ $rent->document }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($rent->rent_update->isNotEmpty())
                                                @if (Storage::exists('public/Rent_File/' . $rent->rent_update->first()->payment_evidence_file) && Storage::size('public/Rent_File/' . $rent->rent_update->first()->payment_evidence_file) > 0)
                                                    <a href="{{ asset('storage/Rent_File/' . $rent->rent_update->first()->payment_evidence_file) }}">Lihat Dokumen</a></td>
                                                @else
                                                    Tidak ada file
                                                @endif
                                            @else
                                                @if (Storage::exists('public/Rent_File/' . $rent->payment_evidence_file) && Storage::size('public/Rent_File/' . $rent->payment_evidence_file) > 0)
                                                    <a href="{{ asset('storage/Rent_File/' . $rent->payment_evidence_file) }}">Lihat Dokumen</a></td>
                                                @else
                                                    Tidak ada file
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if ($rent->rent_update->isNotEmpty())
                                                {{ $rent->rent_update->first()->status }}
                                            @else
                                                {{ $rent->status }}
                                            @endif
                                        </td>
                                        <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $rent->rent_update->isNotEmpty() ? $rent->rent_update->first()->notes : $rent->notes }}">
                                            @if ($rent->rent_update->isNotEmpty())
                                                {!! Str::limit($rent->rent_update->first()->notes, 12, '...') !!}
                                            @else
                                                {!! Str::limit($rent->notes, 12, '...') !!}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <div style="float:right">
                                    {{ $rents->appends(Request::except('page'))->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Create -->
    <div class="modal fade" id="addRentsModal" role="dialog" aria-labelledby="addRentsModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="addRentsModalLabel">Tambah Perjanjian Sewa Baru</h1>
                </div>
                <div class="modal-body">
                    <form action="/rent/store" method="POST" enctype="multipart/form-data">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="inputRentedAddress" class="form-label">Alamat Bangunan</label>
                        <input name="rented_address" type="text" class="form-control" id="inputRentedAddress" placeholder="Alamat Bangunan.." required>
                    </div>
                    <div class="form-group">
                        <label for="inputRentedDetail" class="form-label">Detail/Nama Bangunan</label>
                        <input name="rented_detail" type="text" class="form-control" id="inputRentedDetail" placeholder="Nama Bangunan.." required>
                    </div>
                    <div class="form-group">
                        <label for="inputFirstParty" class="form-label">Nama Pihak Pertama</label>
                        <input name="first_party" type="text" class="form-control" id="inputFirstParty" placeholder="Pihak Pertama.." required>
                    </div>
                    <div class="form-group">
                        <label for="inputSecondParty" class="form-label">Nama Pihak Kedua</label>
                        <input name="second_party" type="text" class="form-control" id="inputSecondParty" placeholder="Pihak Kedua.." required>
                    </div>
                    <div class="form-group">
                        <label for="inputRentPerYear" class="form-label">Nilai Sewa per Tahun</label>
                        <input name="rent_per_year" type="number" class="form-control" id="inputRentPerYear" placeholder="Sewa per Tahun.." required>
                    </div>
                    <div class="form-group">
                        <label for="inputCvcsFund" class="form-label">Dana CV.CS</label>
                        <input name="cvcs_fund" type="number" class="form-control" id="inputCvcsFund" placeholder="Dana CV.CS..">
                    </div>
                    <div class="form-group">
                        <label for="inputOnlineFund" class="form-label">Dana Online</label>
                        <input name="online_fund" type="number" class="form-control" id="inputOnlineFund" placeholder="Dana Online..">
                    </div>
                    <div class="form-group">
                        <label for="inputJoinDate" class="form-label">Tanggal Mulai</label>
                        <input data-format="dd/mm/yyyy" type="text" class="form-control float-right" value="" name="join_date" id="tanggalMulaiSewa" required>
                    </div>
                    <div class="form-group">
                        <label for="inputExpiredDate" class="form-label">Tanggal Berakhir</label>
                        <input data-format="dd/mm/yyyy" type="text" class="form-control float-right" value="" name="expired_date" id="tanggalAkhirSewa" required>
                    </div>
                    <div class="form-group">
                        <label for="inputDeductionEvidence" class="form-label">Bukti Potong</label>
                        <select class="form-control" name="deduction_evidence">
                            <option selected disabled>-- Pilih --</option>
                                <option value="ADA">ADA</option>
                                <option value="TIDAK ADA">TIDAK ADA</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inputDocument" class="form-label">Berkas</label>
                        <select class="form-control" name="document">
                            <option selected disabled>-- Pilih --</option>
                                <option value="ADA">ADA</option>
                                <option value="TIDAK ADA">TIDAK ADA</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inputStatus" class="form-label">Status</label>
                        <select class="form-control" name="status" required>
                            <option selected disabled>-- Pilih --</option>
                                <option value="BERJALAN" selected>BERJALAN</option>
                                <option value="PEMBAHARUAN">PEMBAHARUAN</option>
                                <option value="TUTUP">TUTUP</option>
                                <option value="REFUND">REFUND</option>
                        </select>
                    </div>
                    <div class="form-group" id="inputPaymentEvidence">
                        <label for="inputPaymentEvidence" class="form-label">Upload bukti foto pembayaran (jpg,jpeg,png) </label>
                        <input type="file" name="payment_evidence_file" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="inputMonthBeforeReminder" class="form-label">Reminder Berapa bulan sebelumnya</label>
                        <input name="month_before_reminder" type="number" class="form-control" id="inputMonthBeforeReminder" placeholder="Jumlah Bulan..">
                    </div>
                    <div class="form-group">
                        <label for="inputNotes" class="form-label">Catatan</label>
                        <input name="notes" type="text" class="form-control" id="inputNotes" placeholder="Catatan..">
                    </div>
                </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">BATAL</button>
                        <button type="submit" class="btn btn-primary">SIMPAN</button>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <form action="/rent/import" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal fade importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="importModalLabel">Import Data Perjanjian Sewa Awal</h1>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="">Pilih File</label>
                        <input type="file" class="form-control" name="fileImport">
                        <h5>*Pastikan data yang diinput adalah data perjanjian sewa yang pertama kali didaftarkan</h5>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-primary">IMPORT</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop