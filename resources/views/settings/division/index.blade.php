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
                                <a class="btn btn-info" data-toggle="modal" data-target="#divisionModal" data-toggle="tooltip" data-placement="top" title="Tambah Divisi"><span class="lnr lnr-plus-circle"></span></a>
                            </div>
							<h3 class="panel-title">Data Divisi</h3>
						</div>
						<div class="panel-body table-responsive">
							<table class="table table-hover">
								<thead>
                                <tr>
                                    <th>NO</th>
                                    <th>Divisi</th>
                                    <th>Area</th>
                                    <th>Aksi</th>
                                </tr>
								</thead>
								<tbody>
                                @foreach ($divisions as $division)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $division->division }}</td>
                                    <td>{{ $division->area->area }}</td>
                                    <td>
                                        <a href="/division/{{$division->id}}/edit" class="btn btn-warning btn-sm"><span class="lnr lnr-pencil"></span></a>
                                        <!-- BUTTON DELETE -->
                                        @if ($division->deleted_at)
                                            <a href="/division/{{ $division->id }}/active" class="btn btn-danger btn-xs"
                                                onclick="return confirm('Mengaktifkan kembali division {{ $division->division }}?')"><span class="lnr lnr-cross-circle"></span></a>
                                        @else
                                            <a href="/division/{{$division->id}}/delete" class="btn btn-success btn-xs"
                                                onclick="return confirm('Apalah anda yakin menonaktifkan division {{ $division->division }}?')"><span class="lnr lnr-checkmark-circle"></span></a>
                                        @endif
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
    <div class="modal fade" id="divisionModal" tabindex="-1" role="dialog" aria-labelledby="divisionModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="divisionModalLabel">Tambah Divisi</h1>
                </div>
                <div class="modal-body">
                <form action="/division/create" method="POST">
                {{csrf_field()}}
                <div class="form-group">
                    <label for="inputDivision" class="form-label">Divisi</label>
                    <input name="division" type="text" class="form-control" id="inputDivision" placeholder="Nama divisi.." required>
                </div>
                <div class="form-group">
                    <label for="inputArea" class="form-label">Area</label>
                        <select class="form-control" id="area_id" name="area_id" required>
                            <option selected disabled>-- Pilih Area --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}">
                                    {{ $area->area }}</option>
                            @endforeach
                        </select>
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