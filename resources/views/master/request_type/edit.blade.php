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
                                <h3 class="panel-title">Edit</h3>
                            </div>
                            <div class="panel-body">
                            <form action="/requesttype/{{$request_type->id}}/update" method="POST">
                                {{csrf_field()}}
                                <div class="form-group">
                                    <label for="inputRequestType" class="form-label">Tipe Pengajuan</label>
                                    <input name="request_type" type="text" class="form-control" id="inputRequestType" value="{{$request_type->request_type}}" required>
                                </div>
                                <div class="form-group">
                                    <label for="inputDivisi" class="form-label">Divisi PIC</label>
                                    <select class="form-control" id="pic_division_id" name="pic_division_id" required>
                                            <option selected disabled>-- Pilih Divisi untuk PIC --</option>
                                            @foreach ($divisions as $division)
                                                <option value="{{ $division->id }}" 
                                                    {{ $division->id === $request_type->pic_division_id ? 'selected' : '' }}>
                                                    {{ $division->division }}</option>
                                            @endforeach
                                        </select>
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
@stop