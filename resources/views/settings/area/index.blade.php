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
                                <a class="btn btn-info" data-toggle="modal" data-target="#areaModal"data-toggle="tooltip" data-placement="top" title="Tambah Area"><span class="lnr lnr-plus-circle"></span></a>
                            </div>
                            <h3 class="panel-title">Data Area</h3>
						</div>
						<div class="panel-body">
							<table class="table table-hover">
								<thead>
                                <tr>
                                    <th>NO</th>
                                    <th>Area</th>
                                    <th>Aksi</th>
                                </tr>
								</thead>
								<tbody>
                                @foreach ($areas as $area)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $area->area }}</td>
                                    <td>
                                        <a href="/area/{{$area->id}}/edit" class="btn btn-warning btn-sm"><span class="lnr lnr-pencil"></span></a>
                                        <!-- BUTTON DELETE -->
                                        <!-- <a href="/area/{{$area->id}}/delete" class="btn btn-danger btn-sm" onclick="return confirm('Yakin akan menghapus data ?')">Hapus</a> -->
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
    <div class="modal fade" id="areaModal" tabindex="-1" role="dialog" aria-labelledby="areaModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="areaModalLabel">Tambah Area</h1>
                </div>
                <div class="modal-body">
                <form action="/area/create" method="POST">
                {{csrf_field()}}
                <div class="form-group">
                    <label for="inputArea" class="form-label">Area</label>
                    <input name="area" type="text" class="form-control" id="inputArea" placeholder="Nama Area.." required>
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