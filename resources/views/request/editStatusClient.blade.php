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
                            </div>
                            <div class="panel-body">
                                <form action="/request/{{$requestBarang->id}}/updateStatusClient" method="POST">
                                {{csrf_field()}}
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <input type="hidden" name="dataid" id="dataid" class="form-control" value="{{ $requestBarang->id }}" readonly/>
                                    </div>
                                    <div class="form-group">
                                        <label for="inputStatus" class="form-label">Status Client</label>
                                            <select class="form-control" id="status_client" name="status_client">
                                                <option {{ $requestBarang->status_client == 0 ? 'selected' : '' }} value="0">MENUNGGU</option>
                                                <option {{ $requestBarang->status_client == 1 ? 'selected' : '' }} value="1">DITERIMA</option>
                                            </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="inputUserNotes" class="form-label">Catatan/Masukan</label>
                                        <input type="text" class="form-control float-right" value="" name="user_notes">
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