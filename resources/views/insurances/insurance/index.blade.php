@extends('layouts.master')

@section('content')
    @php
        $canManageInsurance = auth()->user()->canManageInsurance();
    @endphp
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
                                <div class="col-md-12" style="margin-bottom: 20px;">
                                    <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Beranda</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Asuransi</li>
                                    </ol>
                                    </nav>
                                </div>
                                <div class="col-md-12" style="margin-bottom: 20px;">
                                    <div class="col-md-4 text-center">
                                    <form class="form-inline" id="search_form" action="/insurance">
                                        <div class="form-group">
                                        <input type="text" class="form-control" name="search" placeholder="Cari ..." style="width: 140px;">
                                        <a href="javascript:{}" onclick="document.getElementById('search_form').submit();" class="btn btn-info" ><span class="lnr lnr-magnifier"></span></a>
                                        </div>
                                    </form>
                                    </div>
                                    <div class="col-md-3 text-right">
                                        <form class="form-inline" id="inputStatus" action="/insurance">
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
	                                    <div class="col-md-5 text-right">
	                                        @if ($canManageInsurance)
	                                        <a href="/insurance/create" class="btn btn-info" data-toggle="modal" data-target="#addinsurancesModal" data-toggle="tooltip" data-placement="top" title="Tambah data baru"><span class="lnr lnr-plus-circle"></span></a>
	                                        @endif
	                                        <a href="/insurance/export" class="btn btn-primary" data-toggle="tooltip" data-placement="bottom" title="Export Asuransi"><span class="lnr lnr-download"></span></a>
	                                        @if ($canManageInsurance)
	                                        <a class="btn btn-success" data-toggle="modal" data-target=".importModal" data-toggle="tooltip" data-placement="top" title="Import Asuransi"><span class="lnr lnr-upload"></span></a>
	                                        <a href="/insurance/export/template" class="btn btn-default" data-toggle="tooltip" data-placement="top" title="Download template"><span class="lnr lnr-text-align-justify"></span></a>
	                                        @endif
	                                    </div>
                                    <br>
                                </div>
                            </div>
                            <br><br><br>
                            <div class="panel-body table-responsive">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                        <th>Aksi</th>
                                        <th>NO</th>
                                        <th>No Polis</th>
                                        <!-- <th>Alamat Tertanggung</th>
                                        <th>Nama Tertanggung</th> -->
                                        <th>Kode</th>
                                        <th>Detail Asuransi</th>
                                        <!-- <th>Alamat yg diasuransikan</th> -->
                                        <th>Tanggal Berakhir</th>
                                        <th>Asuransi Stok</th>
                                        <th>Nilai Stok</th>
                                        <th>Aktual Stok</th>
                                        <th>Selisih</th>
                                        <th>Status Stok</th>
                                        <th>Asuransi Bangunan</th>
                                        <th>Nilai Bangunan</th>
                                        <th>Kategori</th>
                                        <th>Cakupan</th>
                                        <!-- <th>Tanggal Mulai</th> -->
                                        <th>Status</th>
                                        <th>Catatan</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php
                                        $sortedInsurances = $insurances->sortBy(function ($insurance) {
                                            $expired_date = $insurance->insurance_update->isNotEmpty() ? $insurance->insurance_update->first()->expired_date : $insurance->expired_date;
                                            return Carbon\Carbon::parse($expired_date);
                                        });
                                    @endphp

                                    @foreach ($sortedInsurances as $insurance)
                                    @php
                                        if ($insurance->insurance_update->isNotEmpty()) {
                                            $expiredDate = Carbon\Carbon::parse($insurance->insurance_update->first()->expired_date);
                                        } else {
                                            $expiredDate = Carbon\Carbon::parse($insurance->expired_date);
                                        }

                                        $diffInDays = (int) Carbon\Carbon::now()->diffInDays($expiredDate, false);

                                        $rowStyle = ''; // initialize the variable

                                        if ($diffInDays <= 30 && $diffInDays > 14) {
                                            $rowStyle = 'background-color: #fcf8e3; color: #8a6d3b;';
                                        } elseif ($diffInDays <= 14) {
                                            if ($insurance->insurance_update->isNotEmpty()) {
                                                $status = $insurance->insurance_update->first()->status;
                                                
                                                if ($status === 'BERJALAN' || $status === 'PEMBAHARUAN') {
                                                    $rowStyle = 'background-color: #f2dede; color: #b96564;';
                                                } else {
                                                    $rowStyle = 'background-color: #EAECEA; color: #000000;';
                                                }
                                            } else {
                                                $status = $insurance->status;

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
                                            <!-- <a href="/insurance/{{$insurance->id}}/edit" class="btn btn-warning btn-xs" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a> -->
                                            <!-- BUTTON DELETE -->
                                            <!-- <a href="/insurance/{{$insurance->id}}/delete" class="btn btn-danger btn-xs" onclick="return confirm('Yakin akan menghapus data ?')"><span class="lnr lnr-trash"></span></a> -->
                                            <a href="/insurance/{{$insurance->id}}" class="btn btn-default btn-xs" type="button"><span class="lnr lnr-eye"></span></a>
                                        </td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $insurance->insurance_update->isNotEmpty() ? $insurance->insurance_update->first()->policy_number : $insurance->policy_number }}">
                                            @if ($insurance->insurance_update->isNotEmpty())
                                                {!! Str::limit($insurance->insurance_update->first()->policy_number, 12, '...') !!}
                                            @else
                                                {!! Str::limit($insurance->policy_number, 12, '...') !!}
                                            @endif
                                        </td>
                                        <!-- <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $insurance->insured_address }}">{!! Str::limit($insurance->insured_address, 30, '...') !!}</td>
                                        <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $insurance->insured_name }}">{{ $insurance->insured_name }}</td> -->
                                        <td>{{ $insurance->warehouse_code }}</td>
                                        <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $insurance->insured_detail }}">{!! Str::limit($insurance->insured_detail, 15, '...') !!}</td>
                                        <!-- <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $insurance->risk_address }}">{!! Str::limit($insurance->risk_address, 24, '...') !!}</td> -->
                                        <td><strong>
                                            @if ($insurance->insurance_update->isNotEmpty())
                                                {{ Carbon\Carbon::parse($insurance->insurance_update->first()->expired_date)->format('d M Y') }}
                                            @else
                                                {{ Carbon\Carbon::parse($insurance->expired_date)->format('d M Y') }}
                                            @endif
                                            </strong>
                                        </td>
                                        <td>
                                            @if ($insurance->insurance_update->isNotEmpty())
                                                {{ $insurance->insurance_update->first()->stock_insurance_provider->insurance_provider ?? '' }}
                                            @else
                                            {{ $insurance->stock_insurance_provider->insurance_provider ?? ''}}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($insurance->insurance_update->isNotEmpty())
                                                Rp {{ number_format($insurance->insurance_update->first()->stock_worth, 0, ',', '.') }}
                                            @else
                                                Rp {{ number_format($insurance->stock_worth, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($insurance->insurance_update->isNotEmpty())
                                                Rp {{ number_format($insurance->insurance_update->first()->actual_stock_worth, 0, ',', '.') }}
                                            @else
                                                Rp {{ number_format($insurance->actual_stock_worth, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td><strong style="color: #b96564;">
                                            @if ($insurance->insurance_update->isNotEmpty())
                                                Rp {{ number_format(($insurance->insurance_update->first()->stock_worth - $insurance->insurance_update->first()->actual_stock_worth), 0, ',', '.') }}
                                            @else
                                                Rp {{ number_format(($insurance->stock_worth - $insurance->actual_stock_worth), 0, ',', '.') }}
                                            @endif
                                        </strong></td>
                                        <td>
                                            @if ($insurance->insurance_update->isNotEmpty())
                                                @if ($insurance->insurance_update->first()->actual_stock_worth > $insurance->insurance_update->first()->stock_worth)
                                                    @php
                                                        $threshold = $insurance->insurance_update->first()->stock_worth * 0.3;
                                                        $difference = $insurance->insurance_update->first()->actual_stock_worth - $insurance->insurance_update->first()->stock_worth;
                                                    @endphp
                                                    @if ($difference >= $threshold)
                                                        Overlimit
                                                    @else
                                                        OK
                                                    @endif
                                                @else
                                                    @php
                                                        $threshold = $insurance->insurance_update->first()->stock_worth * 0.3;
                                                        $difference = $insurance->insurance_update->first()->stock_worth - $insurance->insurance_update->first()->actual_stock_worth;
                                                    @endphp
                                                    @if ($difference >= $threshold)
                                                        Underlimit
                                                    @else
                                                        OK
                                                    @endif
                                                @endif
                                            @else
                                                @if ($insurance->actual_stock_worth > $insurance->stock_worth)
                                                    @php
                                                        $threshold = $insurance->stock_worth * 0.3;
                                                        $difference = $insurance->actual_stock_worth - $insurance->stock_worth;
                                                    @endphp
                                                    @if ($difference >= $threshold)
                                                        Overlimit
                                                    @else
                                                        OK
                                                    @endif
                                                @else
                                                    @php
                                                        $threshold = $insurance->stock_worth * 0.3;
                                                        $difference = $insurance->stock_worth - $insurance->actual_stock_worth;
                                                    @endphp
                                                    @if ($difference >= $threshold)
                                                        Underlimit
                                                    @else
                                                        OK
                                                    @endif
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if ($insurance->insurance_update->isNotEmpty())
                                                {{ $insurance->insurance_update->first()->building_insurance_provider->insurance_provider ?? '' }}
                                            @else
                                                {{ $insurance->building_insurance_provider->insurance_provider ?? ''}}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($insurance->insurance_update->isNotEmpty())
                                                Rp {{ number_format($insurance->insurance_update->first()->building_worth, 0, ',', '.') }}
                                            @else
                                                Rp {{ number_format($insurance->building_worth, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td>{{ $insurance->insurance_category->insurance_category }}</td>
                                        <th>{{ $insurance->insurance_scope->insurance_scope }}</th>
                                        <!-- <td><strong>{{ Carbon\Carbon::parse($insurance->join_date)->format('d M Y') }}</strong></td> -->
                                        <td>
                                            @if ($insurance->insurance_update->isNotEmpty())
                                                {{ $insurance->insurance_update->first()->status }}
                                            @else
                                                {{ $insurance->status }}
                                            @endif
                                        </td>
                                        <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $insurance->insurance_update->isNotEmpty() ? $insurance->insurance_update->first()->notes : $insurance->notes }}">
                                            @if ($insurance->insurance_update->isNotEmpty())
                                                {!! Str::limit($insurance->insurance_update->first()->notes, 12, '...') !!}
                                            @else
                                                {!! Str::limit($insurance->notes, 12, '...') !!}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <div style="float:right">
                                    {{ $insurances->appends(Request::except('page'))->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

	    <!-- Modal Create -->
        @if ($canManageInsurance)
	    <div class="modal fade" id="addinsurancesModal" role="dialog" aria-labelledby="addinsurancesModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="addinsurancesModalLabel">Tambah Asuransi Baru</h1>
                </div>
                <div class="modal-body">
                    <form action="/insurance/store" method="POST">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="inputPolicyNumber" class="form-label">No Polis</label>
                        <input name="policy_number" type="text" class="form-control" id="inputPolicyNumber" placeholder="No Polis.." required>
                    </div>
                    <div class="form-group">
                        <label for="inputInsuredAddress" class="form-label">Alamat Tertanggung</label>
                        <input name="insured_address" type="text" class="form-control" id="inputInsuredAddress" placeholder="Alamat Tertanggung..">
                    </div>
                    <div class="form-group">
                        <label for="inputInsuredName" class="form-label">Nama Tertanggung</label>
                        <input name="insured_name" type="text" class="form-control" id="inputInsuredName" placeholder="Nama Tertanggung.." required>
                    </div>
                    <div class="form-group">
                        <label for="inputReminderPhones" class="form-label">Nomor WhatsApp Pengingat</label>
                        <input name="reminder_phones" type="text" class="form-control" id="inputReminderPhones" placeholder="Contoh: 081234567890, 089876543210">
                        <small class="text-muted">Pisahkan dengan koma jika lebih dari satu. Kosongkan untuk memakai nomor di Pengaturan Notifikasi.</small>
                    </div>
                    <div class="form-group">
                        <label for="inputWarehouseCode" class="form-label">Kode Gudang</label>
                        <input name="warehouse_code" type="text" class="form-control" id="inputWarehouseCode" placeholder="Kode Gudang..">
                    </div>
                    <div class="form-group">
                        <label for="inputInsuredDetail" class="form-label">Detail Nama Tertanggung</label>
                        <input name="insured_detail" type="text" class="form-control" id="inputInsuredDetail" placeholder="Detail Nama Tertanggung..">
                    </div>
                    <div class="form-group">
                        <label for="inputRiskAddress" class="form-label">Alamat yang diasuransikan</label>
                        <input name="risk_address" type="text" class="form-control" id="inputRiskAddress" placeholder="Alamat yang diasuransikan.." required>
                    </div>
                    <div class="form-group">
                        <label for="inputStockInprov" class="form-label">Asuransi Stok</label>
                        <select class="form-control" name="stock_inprov_id">
                            <option selected disabled>-- Pilih Provider Asuransi --</option>
                            @foreach ($inprovs as $inprov)
                                <option value="{{ $inprov->id }}">
                                    {{ $inprov->insurance_provider }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inputStockWorth" class="form-label">Nilai Stok</label>
                        <input name="stock_worth" type="number" class="form-control" id="inputStockWorth" placeholder="Nilai Stok..">
                    </div>
                    <div class="form-group">
                        <label for="inputActualStockWorth" class="form-label">Nilai Aktual Stok</label>
                        <input name="actual_stock_worth" type="number" class="form-control" id="inputActualStockWorth" placeholder="Nilai Aktual Stok..">
                    </div>
                    <div class="form-group">
                        <label for="inputStockPremium" class="form-label">Premi Stok</label>
                        <input name="stock_premium" type="number" class="form-control" id="inputStockPremium" placeholder="Premi Stok..">
                    </div>
                    <div class="form-group">
                        <label for="inputBulldingInprov" class="form-label">Asuransi Bangunan</label>
                        <select class="form-control" name="building_inprov_id">
                            <option selected disabled>-- Pilih Provider Asuransi --</option>
                            @foreach ($inprovs as $inprov)
                                <option value="{{ $inprov->id }}">
                                    {{ $inprov->insurance_provider }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inputBuildingWorth" class="form-label">Nilai Bangunan</label>
                        <input name="building_worth" type="number" class="form-control" id="inputBuildingWorth" placeholder="Nilai Bangunan..">
                    </div>
                    <div class="form-group">
                        <label for="inputBuildingPremium" class="form-label">Premi Bangunan</label>
                        <input name="building_premium" type="number" class="form-control" id="inputBuildingPremium" placeholder="Premi Bangunan..">
                    </div>
                    <div class="form-group">
                        <label for="inputInsuranceCategory" class="form-label">Kategori Asuransi</label>
                        <select class="form-control" name="insurance_category_id" required>
                            <option selected disabled>-- Pilih Kategori Asuransi --</option>
                            @foreach ($incategories as $incategory)
                                <option value="{{ $incategory->id }}">
                                    {{ $incategory->insurance_category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inputInsuranceScope" class="form-label">Cakupan Asuransi</label>
                        <select class="form-control" name="insurance_scope_id" required>
                            <option selected disabled>-- Pilih Cakupan Asuransi --</option>
                            @foreach ($inscopes as $inscope)
                                <option value="{{ $inscope->id }}">
                                    {{ $inscope->insurance_scope }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inputJoinDate" class="form-label">Tanggal Mulai</label>
                        <input data-format="dd/mm/yyyy" type="text" class="form-control float-right" value="" name="join_date" id="tanggalMulaiAsuransi" required>
                    </div>
                    <div class="form-group">
                        <label for="inputExpiredDate" class="form-label">Tanggal Berakhir</label>
                        <input data-format="dd/mm/yyyy" type="text" class="form-control float-right" value="" name="expired_date" id="tanggalAkhirAsuransi" required>
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
    <form action="/insurance/import" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal fade importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="importModalLabel">Import Data Asuransi Awal</h1>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="">Pilih File</label>
                        <input type="file" class="form-control" name="fileImport">
                        <h5>*Pastikan data yang diinput adalah data asuransi yang pertama kali didaftarkan</h5>
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
        @endif
	@stop
