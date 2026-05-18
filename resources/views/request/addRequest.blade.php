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
                                    <li class="breadcrumb-item"><a href="/request">Pengajuan</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Tambah Pengajuan</li>
                                </ol>
                                </nav>
                            </div>
                        </div>
                        <div class="panel-body" >
                            <div class="col-md-12">

                                <div class="form-group">
                                    <label for="request_type_id" class="form-label">Tipe Pengajuan</label>
                                    <select class="form-control" id="request_type_id" name="request_type_id" required onchange="toggleQtySisa()">
                                        <option selected disabled>-- Pilih Tipe Pengajuan --</option>
                                        @foreach ($request_types as $reqtype)
                                            <option value="{{ $reqtype->id }}">
                                                {{ $reqtype->request_type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="" id="formaddmanyproduct" >
                                    <form action="/request/store" method="POST" enctype="multipart/form-data">
                                    {{csrf_field()}}
                                    <div class="form-group">
                                        <label for="inputUser" class="form-label hidden">User</label>
                                        <input name="user_id" type="hidden" class="form-control" id="inputUser" value="{{ auth()->user()->id }}">
                                    </div>
                                    <div class="form-group">
                                        <input name="area_id" type="hidden" class="form-control" id="inputArea" value="{{ auth()->user()->division->area->id }}">
                                    </div>
                                    <div class="form-group">
                                        <input name="request_type_id" type="hidden" class="form-control" id="inputRequestTypeId" value="">
                                    </div>
                                    <div>
                                        <div class="form-group" >
                                            <div class="card-body table-responsive p-0" style="min-height: 300px;">
                                                <table class="table table-head-fixed text-nowrap">
                                                    <thead>
                                                        <tr>
                                                            <th>Nama Barang</th>
                                                            <th style="width:15%;" id="th-qty-sisa">Qty Sisa</th>
                                                            <th style="width:15%;">Qty Req</th>
                                                            <th style="width:30%;" id="th-alasan">Alasan</th>
                                                            <th style="width:30%;" id="th-no-nota">Ket / No nota yg akan dikirim</th>
                                                            <th style="width:10%;">
                                                                <a href="#addproduct" class="badge bg-success" id="addProduct">Add <span class="lnr lnr-plus-circle"></span></a>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tableproduct" class="tableproduct">
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="form-group" id="inputRequestFile">
                                            <label for="inputRequestFile" class="form-label" id="label-approved-file">Upload bukti foto bahwa pengajuan telah disetujui Atasan(jpg,jpeg,png,pdf) </label>
                                            <label for="inputRequestFile" class="form-label" id="label-nota-file">Upload bukti foto nota lama dengan nomor nota yang terlihat jelas (jpg,jpeg,png,pdf) </label>
                                            <input type="file" name="request_file" class="form-control">
                                        </div>
                                        <div class="form-group" id="inputRequestFile2">
                                            <label for="inputRequestFile2" class="form-label" id="label-approved-file-2">Upload bukti foto bahwa pengajuan telah disetujui COO (jpg,jpeg,png,pdf) </label>
                                            <input type="file" name="request_file_2" class="form-control">
                                        </div>
                                    </div>
                                    <br>
                                    <button type="submit" class="btn btn-info" onclick="return confirm('Pengajuan ini tanpa revisi, pastikan pengajuan sudah benar!!')">SIMPAN</button>
                                </div>
                                </form>
                            </div>

                        <!-- <div class=""  id="formaddproduct">
                            <form action="/request/store" method="POST" enctype="multipart/form-data">
                            {{csrf_field()}}
                            <div class="form-group">
                                <label for="inputUser" class="form-label hidden">User</label>
                                <input name="user_id" type="hidden" class="form-control" id="inputUser" value="{{ auth()->user()->id }}">
                            </div>
                            <div class="form-group">
                                <input name="request_type_id" type="text" class="form-control" id="inputRequestTypeId2" value="">
                            </div>
                            <div class="form-group">
                                <label for="product_id" class="form-label">Barang</label>
                                <select class="form-control" id="product_id" name="product_id" required>
                                    <option selected disabled>-- Pilih Barang --</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">
                                            {{ $product->product }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="inputQtyRequest" class="form-label">Kuantitas Request</label>
                                <input name="qty_request" type="number" class="form-control" id="inputQtyRequest" placeholder="Kuantitas Request.." required>
                            </div>
                            <div class="form-group">
                                <label for="inputQtyRemaining" class="form-label">Kuantitas Tersisa Saat Ini</label>
                                <input name="qty_remaining" type="number" class="form-control" id="inputQtyRemaining" placeholder="Sisa.." required>
                            </div>
                            <div class="form-group">
                                <label for="inputDescription" class="form-label">Deskripsi Pengajuan</label>
                                <input name="description" type="text" class="form-control" id="inputDescription" placeholder="Deskripsi.." required>
                            </div>
                            <div class="form-group">
                            <label for="inputRequestFile" class="form-label">File Pengajuan</label>
                            <input type="file" name="request_file" class="form-control" required>
                            </div>
                            <br>
                            <button type="submit" class="btn btn-info">SIMPAN</button>
                            </div>
                            </form>
                        </div> -->
                    </div>
                </div>
            </div>
    </div>
</div>
<script>
function toggleQtySisa() {
    var requestTypeId = $('#request_type_id').val();
    var areaId = "{{ auth()->user()->division->area->id }}";

    if (requestTypeId == 3 && (areaId == 3 || areaId == 4 || areaId == 5)) {
        $('#th-qty-sisa').show();
        $('#th-no-nota').show();
        $('#th-alasan').hide();
    } else if (requestTypeId == 2 && (areaId == 3 || areaId == 4 || areaId == 5 || areaId == 11)){
        $('#th-qty-sisa').show();
        $('#th-no-nota').hide();
        $('#th-alasan').show();
    } else {
        $('#th-qty-sisa').hide();
        $('#th-no-nota').hide();
        $('#th-alasan').show();
    }
}
</script>
@stop