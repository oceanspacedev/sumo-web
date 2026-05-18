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
                            <div class="btn-group pull-right">
                                <!-- <a class="btn btn-info" data-toggle="modal" data-target="#requestSettingModal" data-toggle="tooltip" data-placement="top" title="Tambah Pengaturan"><span class="lnr lnr-plus-circle"></span></a> -->
                            </div>
                            <h3 class="panel-title">Pengaturan Dashboard Pengajuan</h3>
						</div>
						<div class="panel-body">
							<table class="table table-hover">
								<thead>
                                <tr>
                                    <th>NO</th>
                                    <th>Detail </th>
                                    <th>Untuk Bulan</th>
                                    <th>Tanggal Dibuka</th>
                                    <th>Tanggal Ditutup</th>
                                    <th>Aksi</th>
                                </tr>
								</thead>
								<tbody>
                                @foreach ($requestSettings as $rs)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $rs->request_detail }}</td>
                                    <td>{{ $rs->request_month == null ? '' : Carbon\Carbon::parse($rs->request_month)->format('M Y') }}</td>
                                    <td>{{ $rs->open_date == null ? '' : Carbon\Carbon::parse($rs->open_date)->format('d M Y') }}</td>
                                    <td>{{ $rs->closed_date == null ? '' : Carbon\Carbon::parse($rs->closed_date)->format('d M Y') }}</td>
                                    <td>
                                        <a href="/request-settings/{{$rs->id}}/edit" class="btn btn-warning btn-sm"><span class="lnr lnr-pencil"></span></a>
                                    </td>
                                </tr>
                                @endforeach
								</tbody>
							</table>
						</div>
					</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="requestSettingModal" tabindex="-1" role="dialog" aria-labelledby="requestSettingModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="roleModalLabel">Tambah Pengaturan</h1>
                </div>
                <div class="modal-body">
                <form action="/request-settings/create" method="POST">
                {{csrf_field()}}
                <div class="form-group">
                    <label for="inputRequestDetail" class="form-label">Detail Pengajuan</label>
                    <input name="request_detail" type="text" class="form-control" id="inputRequestDetail" placeholder="Detail Pengajuan.." required>
                </div>
                <div class="form-group">
                    <label for="inputRequestMonth" class="form-label">Bulan Pengajuan</label>
                    <input type="text" class="form-control float-right" value="" name="request_month" id="requestMonth" required>
                </div>
                <div class="form-group">
                    <label for="inputOpenDate" class="form-label">Tanggal Dibuka</label>
                    <input type="text" class="form-control float-right" value="" name="open_date" id="openDate" required>
                </div>
                <div class="form-group">
                    <label for="inputClosedDate" class="form-label">Tanggal Ditutup</label>
                    <input type="text" class="form-control float-right" value="" name="closed_date" id="closedDate" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">BATAL</button>
                <button type="submit" class="btn btn-primary">SIMPAN</button>
            </form>
            </div>
        </div>
    </div>
@stop