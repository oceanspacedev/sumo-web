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
                            <form action="/user/{{$user->id}}/update" method="POST" enctype="multipart/form-data">
                                {{csrf_field()}}
                                <div class="form-group">
                                    <label for="inputNama" class="form-label">Nama Lengkap</label>
                                    <input name="fullname" type="text" class="form-control" id="inputNama" value="{{$user->fullname}}" required>
                                </div>
                                <div class="form-group">
                                    <label for="inputUsername" class="form-label">Username</label>
                                    <input name="username" type="text" class="form-control" id="inputUsername" value="{{$user->username}}" required>
                                </div>
                                <div class="form-group">
                                    <label for="inputPassword" class="form-label">Password</label>
                                    <input name="password" type="text" class="form-control" id="inputPassword" required>
                                </div>
                                <div class="form-group">
                                    <label for="inputBU" class="form-label">Badan Usaha</label>
                                        <select class="form-control" id="badan_usaha_id" name="badan_usaha_id" required>
                                            <option selected disabled>-- Pilih Badan Usaha --</option>
                                            @foreach ($badan_usahas as $bu)
                                                <option value="{{ $bu->id }}"
                                                    {{ $bu->id === $user->badan_usaha_id ? 'selected' : '' }}>
                                                    {{ $bu->badan_usaha }}</option>
                                            @endforeach
                                        </select>
                                </div>
                                <div class="form-group">
                                    <label for="division_id" class="form-label">Divisi</label>
                                        <select class="form-control" id="division_id" name="division_id"
                                            required>
                                            @foreach ($division as $division)
                                                <option value="{{ $division->id }}"
                                                    {{ $division->id === $user->division_id ? 'selected' : '' }}>
                                                    {{ $division->division }}</option>
                                            @endforeach
                                        </select>
                                </div>
                                <div class="form-group">
                                    <label for="inputRole" class="form-label">Role</label>
                                        <select class="form-control" id="role_id" name="role_id" required>
                                            <option selected disabled>-- Pilih Role --</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}"
                                                    {{ $role->id === $user->role_id ? 'selected' : '' }}>
                                                    {{ $role->role }}</option>
                                            @endforeach
                                        </select>
                                </div>
                                <div class="form-group">
                                    <label for="inputApproval" class="form-label">Approval</label>
                                        <select class="form-control" id="approval_id" name="approval_id" required>
                                            <option selected disabled>-- Pilih Approval --</option>
                                            @foreach ($approvals as $approval)
                                                <option value="{{ $approval->id }}"
                                                    {{ $approval->id === $user->approval_id ? 'selected' : '' }}>
                                                    {{ $approval->fullname }}</option>
                                            @endforeach
                                        </select>
                                </div>
                                <div class="form-group">
                                    <label for="inputAvatar" class="form-label">Avatar</label>
                                    <input type="file" name="profile_picture" class="form-control">
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