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
                                    <h3 class="panel-title">Data Kategori Asuransi</h3>
                                    <br>
	                                </div>
	                                <div class="col-md-6 text-right">
	                                    @if ($canManageInsurance)
	                                    <a href="/incategory/create" class="btn btn-info" data-toggle="modal" data-target="#addincategoriesModal" data-toggle="tooltip" data-placement="top" title="Tambah data"><span class="lnr lnr-plus-circle"></span></a>
	                                    @endif
	                                </div>
                            </div>
                            <br><br><br>
                            <div class="panel-body table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
	                                        <th>NO</th>
	                                        <th>Kategori Asuransi</th>
	                                        @if ($canManageInsurance)
	                                        <th>Aksi</th>
	                                        @endif
	                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($incategories as $incategory)
                                    <tr>
	                                        <td>{{ $loop->iteration }}</td>
	                                        <td>{{ $incategory->insurance_category }}</td>
	                                        @if ($canManageInsurance)
	                                        <td>
	                                            <a href="/incategory/{{$incategory->id}}/edit" class="btn btn-warning" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a>
	                                            <!-- BUTTON DELETE -->
	                                            <!-- <a href="/incategory/{{$incategory->id}}/delete" class="btn btn-danger btn-sm" onclick="return confirm('Yakin akan menghapus data ?')">Hapus</a> -->
	                                        </td>
	                                        @endif
	                                    </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <div style="float:right">
                                    {{ $incategories->links() }}
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
	    <div class="modal fade" id="addincategoriesModal" role="dialog" aria-labelledby="addincategoriesModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="addincategoriesModalLabel">Tambah Kategori Asuransi</h1>
                </div>
                <div class="modal-body">
                    <form action="/incategory/store" method="POST">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="inputincategory" class="form-label">Kategori Asuransi</label>
                        <input name="insurance_category" type="text" class="form-control" id="inputincategory" placeholder="Kategori.." required>
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
