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
                                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                                    </ol>
                                    </nav>
                                </div>
                                <div class="col-md-12" style="margin-bottom: 20px;">
                                    <h3 class="panel-title">Pengajuan - {{ $requestBarang->user->fullname }}</h3>
                                </div>
                            </div>
                            <div class="panel-body">
                                <form action="/request/{{$requestBarang->id}}/updateStatus" method="POST">
                                {{csrf_field()}}
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <input type="hidden" name="id" id="id" class="form-control" value="{{ $requestBarang->id }}" readonly/>
                                    </div>
                                    <div class="form-group">
                                        <label for="inputStatus" class="form-label">Status</label>
                                            <select class="form-control" id="status" name="status">
                                                <option {{ $requestBarang->status_client == 0 ? 'selected' : '' }} value="PENDING">MENUNGGU</option>
                                                <option {{ $requestBarang->status_client == 3 ? 'selected' : '' }} value="PROCESSED">DIPROSES</option>
                                                <option {{ $requestBarang->status_client == 1 ? 'selected' : '' }} value="CLOSED">SELESAI</option>
                                                <option {{ $requestBarang->status_client == 2 ? 'selected' : '' }} value="CANCELLED">DIBATALKAN</option>
                                            </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="inputNotes" class="form-label">Catatan</label>
                                        <input type="text" class="form-control float-right" value="{{ $requestBarang->notes }}" name="notes">
                                    </div>
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