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
                                    <li class="breadcrumb-item"><a href="/request/{{$requestId->id}}">Detail Pengajuan</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                                </ol>
                                </nav>
                            </div>
						</div>
						<div class="panel-body table-responsive">
                            <div class="col-md-12">
                                <form action="/request/{{$detail->id}}/updateRequest" method="POST">
                                {{csrf_field()}}
                                <div class="form-group">
                                    <input type="hidden" name="id" id="id" class="form-control" value="{{ $detail->id }}"/>
                                </div>
                                <div class="form-group">
                                    <!-- <label for="inputRequestId" class="form-label">ID REQUEST</label> -->
                                    <input type="hidden" name="request_id" id="request_id" class="form-control" value="{{ $detail->request_id }}"/>
                                </div>
                                <div class="form-group">
                                    <label for="inputProduct" class="form-label">Nama Barang</label>
                                    <input type="text" name="product_id" id="product_id" class="form-control" value="{{ $detail->product->product }}" readonly/>
                                </div>
                                <div class="form-group">
                                    <label for="inputProduct" class="form-label">Jumlah Sisa</label>
                                    <input type="text" name="qty_remaining" id="qty_remaining" class="form-control" value="{{ $detail->qty_remaining }}" readonly/>
                                </div>
                                <div class="form-group">
                                    <label for="inputProduct" class="form-label">Jumlah Request</label>
                                    <input type="text" name="qty_request" id="qty_request" class="form-control" value="{{ $detail->qty_request }}" readonly/>
                                </div>
                                <div class="form-group">
                                    <label for="inputProduct" class="form-label">Jumlah yang disetujui</label>
                                    <input type="text" name="qty_approved" id="qty_approved" class="form-control" value="{{ $detail->qty_approved }}"/>
                                </div>
                                    <button type="submit" class="btn btn-warning">REVISI</button>
                                </form>
                            </div>
						</div>
					</div>
                </div>
            </div>
        </div>
    </div>
@stop