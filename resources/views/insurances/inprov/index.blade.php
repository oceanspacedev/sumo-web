@extends('layouts.master')

@section('content')
    @php
        $canManageInsurance = auth()->user()->canManageInsurance();
    @endphp
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
                                <div class="col-md-6">
                                    <h3 class="panel-title">Data Provider Asuransi</h3>
                                    <br>
	                                </div>
	                                <div class="col-md-6 text-right">
	                                    @if ($canManageInsurance)
	                                    <a href="/inprov/create" class="btn btn-info" data-toggle="modal" data-target="#addinprovsModal" data-toggle="tooltip" data-placement="top" title="Tambah data"><span class="lnr lnr-plus-circle"></span></a>
	                                    @endif
	                                </div>
                            </div>
                            <br><br><br>
                            <div class="panel-body table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
	                                        <th>NO</th>
	                                        <th>Nama Provider</th>
	                                        @if ($canManageInsurance)
	                                        <th>Aksi</th>
	                                        @endif
	                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($inprovs as $inprov)
                                    <tr>
	                                        <td>{{ $loop->iteration }}</td>
	                                        <td>{{ $inprov->insurance_provider }}</td>
	                                        @if ($canManageInsurance)
	                                        <td>
	                                            <a href="/inprov/{{$inprov->id}}/edit" class="btn btn-warning" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a>
	                                            <!-- BUTTON DELETE -->
	                                            <!-- <a href="/inprov/{{$inprov->id}}/delete" class="btn btn-danger btn-sm" onclick="return confirm('Yakin akan menghapus data ?')">Hapus</a> -->
	                                        </td>
	                                        @endif
	                                    </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <div style="float:right">
                                    {{ $inprovs->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

	    <!-- Modal Create -->
        @if ($canManageInsurance)
	    <div class="modal fade" id="addinprovsModal" role="dialog" aria-labelledby="addinprovsModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="addinprovsModalLabel">Tambah Provider</h1>
                </div>
                <div class="modal-body">
                    <form action="/inprov/store" method="POST">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="inputInprov" class="form-label">Nama Provider</label>
                        <input name="insurance_provider" type="text" class="form-control" id="inputInprov" placeholder="Nama.." required>
                    </div>
                </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">BATAL</button>
                        <button type="submit" class="btn btn-primary">SIMPAN</button>
                    </form>
                    </div>
                </div>
            </div>
	        </div>
	    </div>
        @endif
	@stop
