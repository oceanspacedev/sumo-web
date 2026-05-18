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
                                        <li class="breadcrumb-item"><a href="/problemReport">Pelaporan</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                                    </ol>
                                    </nav>
                                </div>
                                <div class="col-md-12" style="margin-bottom: 20px;">
                                    <h3 class="panel-title">Pelaporan - {{ $problem->user->fullname }}</h3>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="col-md-12">
                                    <form action="/problemReport/{{$problem->id}}/updateStatus" method="POST" enctype="multipart/form-data">
                                        {{csrf_field()}}
                                        <div class="form-group">
                                            <!-- <label for="problemid" class="form-label">ID Pelaporan</label> -->
                                            <input type="hidden" name="id" id="id" class="form-control" value="{{ $problem->id }}" readonly/>
                                        </div>
                                        @if ($problem->scheduled_at == null)
                                        <div class="form-group">
                                            <label for="inputScheduledAt" class="form-label">Penjadwalan</label>
                                            <input type="text" class="form-control float-right" value="" name="scheduled_at" id="tanggalScheduled">
                                        </div>
                                        @endif
                                        <div class="form-group">
                                            <label for="inputResultDesc" class="form-label">Hasil Pengerjaan</label>
                                            <input type="text" class="form-control float-right" value="" name="result_desc">
                                        </div>
                                        <div class="form-group">
                                            <label for="inputStatus" class="form-label">Status</label>
                                                <select class="form-control" id="status" name="status">
                                                    <option selected value="PENDING">MENUNGGU</option>
                                                    <option value="CLOSED">SELESAI</option>
                                                    <option value="CANCELLED">DIBATALKAN</option>
                                                </select>
                                        </div>
                                        <div class="form-group" id="inputPhotoAfter">
                                            <label for="inputPhotoAfter" class="form-label">Upload bukti foto pengerjaan </label>
                                            <input type="file" name="photo_after" class="form-control">
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