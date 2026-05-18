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
                            <div class="col-md-12" style="margin-bottom: 10px;">
                                <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="/dashboard">Beranda</a></li>
                                    <li class="breadcrumb-item"><a href="/insurance">Asuransi</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Asuransi Update</li>
                                </ol>
                                </nav>
                            </div>
						</div>
						<div class="panel-body table-responsive">
                            <div class="col-md-12">
                                <div class="col-md-5 text-left mb-100">
                                    <h5><strong>Polis Kontrak Awal : </strong>{{ $detailInsurance->policy_number }}</h5>
                                    <h5><strong>Detail Asuransi : </strong>{{ $detailInsurance->insured_detail }}</h5>
                                    <h5><strong>Alamat yang diasuransikan : </strong>{{ $detailInsurance->risk_address }}</h5>
                                    <h5><strong>Nama Tertanggung : </strong>{{ $detailInsurance->insured_name }}</h5>
                                    <h5><strong>Alamat Tertanggung : </strong>{{ $detailInsurance->insured_address }}</h5>
                                    <h5><strong>Tanggal Mulai : </strong>{{ Carbon\Carbon::parse($detailInsurance->join_date)->format('d M Y') }}</h5>
                                    <h5><strong>Tanggal Akhir : </strong>{{ Carbon\Carbon::parse($detailInsurance->expired_date)->format('d M Y') }}</h5>
                                    <h5><strong>Status : </strong>{{ $detailInsurance->status }}</h5>
                                </div>
                                <div class="col-md-4 text-left mb-100">
                                    <h5><strong>Asuransi Stok : </strong>{{ $detailInsurance->stock_insurance_provider->insurance_provider ?? '' }}</h5>
                                    <h5><strong>Nilai Stok : </strong>Rp {{ number_format($detailInsurance->stock_worth, 0, ',', '.') }}</h5>
                                    <h5><strong>Premi Stok : </strong>Rp {{ number_format($detailInsurance->stock_premium, 0, ',', '.') }}</h5>
                                    <h5><strong>Asuransi Bangunan : </strong>{{ $detailInsurance->building_insurance_provider->insurance_provider ?? '' }}</h5>
                                    <h5><strong>Nilai Bangunan : </strong>Rp {{ number_format($detailInsurance->building_worth, 0, ',', '.') }}</h5>
                                    <h5><strong>Premi Bangunan : </strong>Rp {{ number_format($detailInsurance->building_premium, 0, ',', '.') }}</h5>
                                    <h5><strong>Catatan : </strong>{{ $detailInsurance->notes }}</h5>
                                </div>
	                                <div class="col-md-3 text-right">
	                                    <div class="row">
	                                        @if ($canManageInsurance)
	                                        <a href="/insurance/{{$detailInsurance->id}}/edit" class="btn btn-warning" data-toggle="tooltip" data-placement="bottom" title="Edit Kontrak Awal" type="button"><span class="lnr lnr-pencil"></span></a>
	                                        @endif
	                                        <a href="/insurance/{{$detailInsurance->id}}/exportUpdate" class="btn btn-primary" data-toggle="tooltip" data-placement="bottom" title="Export Asuransi"><span class="lnr lnr-download"></span></a>
	                                        @if ($canManageInsurance)
	                                        <a class="btn btn-success" data-toggle="modal" data-target=".importModal" data-toggle="tooltip" data-placement="bottom" title="Import Asuransi"><span class="lnr lnr-upload"></span></a>
	                                        <a href="/insurance/export/templateUpdate" class="btn btn-default" data-toggle="tooltip" data-placement="bottom" title="Download template"><span class="lnr lnr-text-align-justify"></span></a>
	                                        @endif
	                                    </div>
	                                    @if ($canManageInsurance)
	                                    <br>
	                                    <div class="row">
	                                        <a href="/insurance/createUpdate" class="btn btn-success" data-toggle="modal" data-target="#addinsuranceUpdateModal" data-toggle="tooltip" data-placement="top" title="Update Asuransi"><span class="lnr lnr-plus-circle"></span> Update</a>
	                                    </div>
	                                    @endif
	                                </div>
                            </div>
                            <div class="col-md-12">
                                <hr><br>
                            </div>
                            <br><br>
							<table class="table table-hover">
								<thead>
                                <tr>
                                    <th>Aksi</th>
                                    <th>NO</th>
                                    <th>No Polis</th>
                                    <th>Asuransi Stok</th>
                                    <th>Nilai Stok</th>
                                    <th>Premi Stok</th>
                                    <th>Aktual Stok</th>
                                    <th>Selisih</th>
                                    <th>Asuransi Bangunan</th>
                                    <th>Nilai Bangunan</th>
                                    <th>Premi Bangunan</th>
                                    <th>Tanggal Mulai</th>
                                    <th>Tanggal Berakhir</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                </tr>
								</thead>
								<tbody>
                                    @if (!$detailInsurance)
                                        <tr>
                                            <td colspan="9">No data found.</td>
                                        </tr>
                                    @elseif ($detailInsurance->insurance_update->isEmpty())
                                        <tr>
                                            <td colspan="9">No updates found.</td>
                                        </tr>
                                    @else
                                        @foreach ($detailInsurance->insurance_update->sortByDesc('expired_date') as $detail)
                                        @php
                                            $expiredDate = Carbon\Carbon::parse($detail->expired_date);
                                            $diffInDays = Carbon\Carbon::now()->diffInDays($expiredDate, false);

                                            if ($diffInDays <= 30 && $diffInDays > 14) {
                                                $rowStyle = 'background-color: #fcf8e3; color: #8a6d3b;';
                                            } elseif ($diffInDays <= 14) {
                                                $rowStyle = 'background-color: #f2dede; color: #b96564;';
                                            } else {
                                                $rowStyle = 'background-color: white;';
                                            }
                                        @endphp
	                                        <tr style="{{$rowStyle}}">
	                                            <td>
	                                            @if ($canManageInsurance)
	                                            <a href="/insurance/{{$detail->id}}/{{$detailInsurance->id}}/editUpdate" class="btn btn-warning btn-xs" type="button"><span class="lnr lnr-pencil"></span></a>
	                                            @endif
	                                            <!-- BUTTON DELETE -->
	                                            <!-- <a href="/insurance/{{$detail->id}}/deleteUpdate/{{$detailInsurance->id}}" class="btn btn-danger btn-xs" onclick="return confirm('Yakin akan menghapus data ?')"><span class="lnr lnr-trash"></span></a> -->
	                                            </td> 
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $detail->policy_number }}</td>
                                            <td>{{ $detail->stock_insurance_provider->insurance_provider ?? ''}}</td>
                                            <td>Rp {{ number_format($detail->stock_worth, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format($detail->stock_premium, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format($detail->actual_stock_worth, 0, ',', '.') }}</td>
                                            <td><strong style="color: #b96564;"> Rp {{ number_format(($detail->stock_worth - $detail->actual_stock_worth), 0, ',', '.') }} </strong></td>
                                            <td>{{ $detail->building_insurance_provider->insurance_provider ?? '' }}</td>
                                            <td>Rp {{ number_format($detail->building_worth, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format($detail->building_premium, 0, ',', '.') }}</td>
                                            <td><strong>{{ Carbon\Carbon::parse($detail->join_date)->format('d M Y') }}</strong></td>
                                            <td><strong>{{ Carbon\Carbon::parse($detail->expired_date)->format('d M Y') }}</strong></td>
                                            <!-- <td>{{ $diffInDays }}</td> -->
                                            <td>{{ $detail->status }}</td>
                                            <td>{{ $detail->notes }}</td>
                                        </tr>
                                        @endforeach
                                    @endif
								</tbody>
							</table>
						</div>
					</div>
                </div>
            </div>
        </div>
    </div>

	    <!-- Modal Create -->
        @if ($canManageInsurance)
	    <div class="modal fade" id="addinsuranceUpdateModal" role="dialog" aria-labelledby="addinsuranceUpdateModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="addinsurancesModalLabel">Tambah Update Asuransi</h1>
                </div>
                <div class="modal-body">
                    <form action="/insurance/storeUpdate" method="POST">
                    {{csrf_field()}}
                    <div class="form-group">
                        <input name="insurance_id" type="hidden" class="form-control" value="{{ $detailInsurance->id }}">
                    </div>
                    <div class="form-group">
                        <label for="inputPolicyNumber" class="form-label">No Polis</label>
                        <input name="policy_number" type="text" class="form-control" id="inputPolicyNumber" placeholder="No Polis.." required>
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
                        <label for="inputJoinDate" class="form-label">Tanggal Mulai</label>
                        <input type="text" class="form-control float-right" value="" name="join_date" id="tanggalMulaiAsuransi" required>
                    </div>
                    <div class="form-group">
                        <label for="inputExpiredDate" class="form-label">Tanggal Berakhir</label>
                        <input type="text" class="form-control float-right" value="" name="expired_date" id="tanggalAkhirAsuransi" required>
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
    <form action="/insurance/importUpdate" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal fade importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="importModalLabel">Import Data Asuransi Update</h1>
                </div>
                <div class="form-group">
                    <input name="insurance_id" type="hidden" class="form-control" value="{{ $detailInsurance->id }}">
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="">Pilih File</label>
                        <input type="file" class="form-control" name="fileImport">
                        <h5>*Pastikan data yang diinput adalah data asuransi perpanjangan</h5>
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
