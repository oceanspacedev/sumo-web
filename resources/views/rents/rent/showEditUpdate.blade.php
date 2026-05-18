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
                                <div class="col-md-12" style="margin-bottom: 20px;">
                                    <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Beranda</a></li>
                                        <li class="breadcrumb-item"><a href="/rent">Perjanjian Sewa</a></li>
                                        <li class="breadcrumb-item"><a href="/rent/{{$rentId->id}}">Detail Perjanjian Sewa</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                                    </ol>
                                    </nav>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="col-md-12">
                                    <form action="/rent/{{$rent->id}}/updateRentUpdate" method="POST" enctype="multipart/form-data">
                                        {{csrf_field()}}
                                        <div class="form-group">
                                            <input name="rent_id" type="hidden" class="form-control" value="{{$rent->rent_id}}" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputFirstParty" class="form-label">Nama Pihak Pertama</label>
                                            <input name="first_party" type="text" class="form-control" id="inputFirstParty" value="{{$rent->first_party}}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputSecondParty" class="form-label">Nama Pihak Kedua</label>
                                            <input name="second_party" type="text" class="form-control" id="inputSecondParty" value="{{$rent->second_party}}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputRentPerYear" class="form-label">Nilai Sewa per Tahun</label>
                                            <input name="rent_per_year" type="number" class="form-control" id="inputRentPerYear" value="{{$rent->rent_per_year}}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputCvcsFund" class="form-label">Dana CV.CS</label>
                                            <input name="cvcs_fund" type="number" class="form-control" id="inputCvcsFund" value="{{$rent->cvcs_fund}}">
                                        </div>
                                        <div class="form-group">
                                            <label for="inputOnlineFund" class="form-label">Dana Online</label>
                                            <input name="online_fund" type="number" class="form-control" id="inputOnlineFund" value="{{$rent->online_fund}}">
                                        </div>
                                        <div class="form-group">
                                            <label for="inputJoinDate" class="form-label">Tanggal Mulai</label>
                                            <input data-format="dd/mm/yyyy" type="text" class="form-control float-right" value="{{ Carbon\Carbon::parse($rent->join_date)->format('d/m/Y') }}" name="join_date" id="tanggalMulaiSewa" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputExpiredDate" class="form-label">Tanggal Berakhir</label>
                                            <input data-format="dd/mm/yyyy" type="text" class="form-control float-right" value="{{ Carbon\Carbon::parse($rent->expired_date)->format('d/m/Y') }}" name="expired_date" id="tanggalAkhirSewa" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputDeductionEvidence" class="form-label">Bukti Potong</label>
                                            <select class="form-control" name="deduction_evidence">
                                                <option selected disabled>-- Pilih --</option>
                                                    <option value="ADA"
                                                        {{ $rent->deduction_evidence === 'ADA' ? 'selected' : '' }}>
                                                        ADA</option>
                                                    <option value="TIDAK ADA"
                                                        {{ $rent->document === 'TIDAK ADA' ? 'selected' : '' }}>
                                                        TIDAK ADA</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputDocument" class="form-label">Berkas</label>
                                            <select class="form-control" name="document">
                                                <option selected disabled>-- Pilih --</option>
                                                    <option value="ADA"
                                                        {{ $rent->deduction_evidence === 'ADA' ? 'selected' : '' }}>
                                                        ADA</option>
                                                    <option value="TIDAK ADA"
                                                        {{ $rent->document === 'TIDAK ADA' ? 'selected' : '' }}>
                                                        TIDAK ADA</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="inputStatus" class="form-label">Status</label>
                                            <select class="form-control" name="status">
                                                <option selected disabled>-- Pilih --</option>
                                                    <option value="BERJALAN"
                                                        {{ $rent->status === 'BERJALAN' ? 'selected' : '' }}>
                                                        BERJALAN</option>
                                                    <option value="PEMBAHARUAN"
                                                        {{ $rent->status === 'PEMBAHARUAN' ? 'selected' : '' }}>
                                                        PEMBAHARUAN</option>
                                                    <option value="TUTUP"
                                                        {{ $rent->status === 'TUTUP' ? 'selected' : '' }}>
                                                        TUTUP</option>
                                                    <option value="REFUND"
                                                        {{ $rent->status === 'REFUND' ? 'selected' : '' }}>
                                                        REFUND</option>
                                            </select>
                                        </div>
                                        <div class="form-group" id="inputPaymentEvidence">
                                            <label for="inputPaymentEvidence" class="form-label">Upload bukti foto pembayaran (jpg,jpeg,png) </label>
                                            <input type="file" name="payment_evidence_file" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label for="inputMonthBeforeReminder" class="form-label">Reminder Berapa bulan sebelumnya</label>
                                            <input name="month_before_reminder" type="number" class="form-control" id="inputMonthBeforeReminder" value="{{$rent->month_before_reminder}}">
                                        </div>
                                        <div class="form-group">
                                            <label for="inputNotes" class="form-label">Catatan</label>
                                            <input name="notes" type="text" class="form-control" id="inputNotes" value="{{$rent->notes}}">
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