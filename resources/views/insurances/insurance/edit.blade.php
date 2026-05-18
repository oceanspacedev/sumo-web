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
                                <div class="col-md-12" style="margin-bottom: 10px;">
                                    <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Beranda</a></li>
                                        <li class="breadcrumb-item"><a href="/insurance">Asuransi</a></li>
                                        <li class="breadcrumb-item"><a href="/insurance/{{$insurance->id}}">Asuransi Update</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                                    </ol>
                                    </nav>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="col-md-12">
                                    <form action="/insurance/{{$insurance->id}}/update" method="POST">
                                        {{csrf_field()}}
                                        <div class="form-group">
                                            <label for="inputPolicyNumber" class="form-label">No Polis</label>
                                            <input name="policy_number" type="text" class="form-control" value="{{$insurance->policy_number}}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputInsuredAddress" class="form-label">Alamat Tertanggung</label>
                                            <input name="insured_address" type="text" class="form-control" value="{{$insurance->insured_address}}">
                                        </div>
                                        <div class="form-group">
                                            <label for="inputInsuredName" class="form-label">Nama Tertanggung</label>
                                            <input name="insured_name" type="text" class="form-control" value="{{$insurance->insured_name}}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputWarehouseCode" class="form-label">Kode Gudang</label>
                                            <input name="warehouse_code" type="text" class="form-control" id="inputWarehouseCode" value="{{$insurance->warehouse_code}}">
                                        </div>
                                        <div class="form-group">
                                            <label for="inputInsuredDetail" class="form-label">Detail Nama Tertanggung</label>
                                            <input name="insured_detail" type="text" class="form-control" value="{{$insurance->insured_detail}}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputRiskAddress" class="form-label">Alamat yang diasuransikan</label>
                                            <input name="risk_address" type="text" class="form-control" value="{{$insurance->risk_address}}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputStockInprov" class="form-label">Asuransi Stok</label>
                                            <select class="form-control" name="stock_inprov_id">
                                                <option selected disabled>-- Pilih Provider Asuransi --</option>
                                                @foreach ($inprovs as $inprov)
                                                    <option value="{{ $inprov->id }}"
                                                        {{ $inprov->id === $insurance->stock_inprov_id ? 'selected' : '' }}>
                                                        {{ $inprov->insurance_provider }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputStockWorth" class="form-label">Nilai Stok</label>
                                            <input name="stock_worth" type="number" class="form-control" value="{{$insurance->stock_worth}}" >
                                        </div>
                                        <div class="form-group">
                                            <label for="inputActualStockWorth" class="form-label">Nilai Aktual Stok</label>
                                            <input name="actual_stock_worth" type="number" class="form-control" id="inputActualStockWorth" value="{{$insurance->actual_stock_worth}}">
                                        </div>
                                        <div class="form-group">
                                            <label for="inputStockPremium" class="form-label">Premi Stok</label>
                                            <input name="stock_premium" type="number" class="form-control" id="inputStockPremium" value="{{$insurance->stock_premium}}" >
                                        </div>
                                        <div class="form-group">
                                            <label for="inputBulldingInprov" class="form-label">Asuransi Bangunan</label>
                                            <select class="form-control" name="building_inprov_id">
                                                <option selected disabled>-- Pilih Provider Asuransi --</option>
                                                @foreach ($inprovs as $inprov)
                                                    <option value="{{ $inprov->id }}"
                                                        {{ $inprov->id === $insurance->building_inprov_id ? 'selected' : '' }}>
                                                        {{ $inprov->insurance_provider }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputBuildingWorth" class="form-label">Nilai Bangunan</label>
                                            <input name="building_worth" type="number" class="form-control" value="{{$insurance->building_worth}}">
                                        </div>
                                        <div class="form-group">
                                            <label for="inputBuildingPremium" class="form-label">Premi Bangunan</label>
                                            <input name="building_premium" type="number" class="form-control" id="inputBuildingPremium" value="{{$insurance->building_premium}}">
                                        </div>
                                        <div class="form-group">
                                            <label for="inputInsuranceCategory" class="form-label">Kategori Asuransi</label>
                                            <select class="form-control" name="insurance_category_id" required>
                                                <option selected disabled>-- Pilih Kategori Asuransi --</option>
                                                @foreach ($incategories as $incategory)
                                                    <option value="{{ $incategory->id }}"
                                                        {{ $incategory->id === $insurance->insurance_category_id ? 'selected' : '' }}>
                                                        {{ $incategory->insurance_category }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputInsuranceScope" class="form-label">Cakupan Asuransi</label>
                                            <select class="form-control" name="insurance_scope_id" required>
                                                <option selected disabled>-- Pilih Cakupan Asuransi --</option>
                                                @foreach ($inscopes as $inscope)
                                                    <option value="{{ $inscope->id }}"
                                                        {{ $inscope->id === $insurance->insurance_scope_id ? 'selected' : '' }}>
                                                        {{ $inscope->insurance_scope }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputJoinDate" class="form-label">Tanggal Mulai</label>
                                            <input data-format="dd/mm/yyyy" type="text" class="form-control float-right" value="{{ Carbon\Carbon::parse($insurance->join_date)->format('d/m/Y') }}" name="join_date" id="tanggalMulaiAsuransi" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputExpiredDate" class="form-label">Tanggal Berakhir</label>
                                            <input data-format="dd/mm/yyyy" type="text" class="form-control float-right" value="{{ Carbon\Carbon::parse($insurance->expired_date)->format('d/m/Y') }}" name="expired_date" id="tanggalAkhirAsuransi" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputStatus" class="form-label">Status</label>
                                            <select class="form-control" name="status" required>
                                                <option selected disabled>-- Pilih Status --</option>
                                                    <option value="BERJALAN"
                                                        {{ $insurance->status === 'BERJALAN' ? 'selected' : '' }}>
                                                        BERJALAN</option>
                                                    <option value="PEMBAHARUAN"
                                                        {{ $insurance->status === 'PEMBAHARUAN' ? 'selected' : '' }}>
                                                        PEMBAHARUAN</option>
                                                    <option value="TUTUP"
                                                        {{ $insurance->status === 'TUTUP' ? 'selected' : '' }}>
                                                        TUTUP</option>
                                                    <option value="REFUND"
                                                        {{ $insurance->status === 'REFUND' ? 'selected' : '' }}>
                                                        REFUND</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputNotes" class="form-label">Catatan</label>
                                            <input name="notes" type="text" class="form-control" id="inputNotes" value="{{$insurance->notes}}">
                                        </div>
                                        <br>
                                        <button type="submit" class="btn btn-warning">UPDATE</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop