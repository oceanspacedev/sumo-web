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
                                        <li class="breadcrumb-item active" aria-current="page">Edit Pemohon</li>
                                    </ol>
                                    </nav>
                                </div>
                                <div class="col-md-12" style="margin-bottom: 20px;">
                                    <h3 class="panel-title">Pengajuan - {{ $requestBarang->user->fullname }}</h3>
                                </div>
                            </div>
                            <div class="panel-body">
                                <form action="/request/{{$requestBarang->id}}/updateApplicant" method="POST">
                                {{csrf_field()}}
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <input type="text" name="id" id="id" class="form-control" value="{{ $requestBarang->id }}" readonly/>
                                        <input type="text" name="user_id_before" class="form-control" value="{{ $requestBarang->user_id }}" readonly/>
                                        <input type="text" name="EditType" class="form-control" value="editApplicant" readonly/>
                                    </div>
                                    <div class="form-group">
                                    <label for="inputUser" class="form-label">User</label>
                                        <select class="form-control" id="user_id" name="user_id" required>
                                            <option selected disabled>-- Pilih User --</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}"
                                                    {{ $user->id === $user->user_id ? 'selected' : '' }}>
                                                    {{ $user->fullname }}</option>
                                            @endforeach
                                        </select>
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