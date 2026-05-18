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
                            <div class="col-md-2">
                                <h3 class="panel-title">Data User</h3>
                            </div>
                            <div class="col-md-4 text-right">
                                <form class="form-inline" id="my_form" action="/user">
                                    <div class="form-group">
                                      <input type="text" class="form-control" name="search" placeholder="Enter your text">
                                      <a href="javascript:{}" onclick="document.getElementById('my_form').submit();" class="btn btn-info" ><span class="lnr lnr-magnifier"></span></a>
                                    </div>
                                  </form>
                            </div>
                            <div class="col-md-6 text-right">
                                <a class="btn btn-info" data-toggle="modal" data-target="#userModal"data-toggle="tooltip" data-placement="top" title="Tambah User"><span class="lnr lnr-plus-circle"></span></a>
                                <a href="/user/export" class="btn btn-primary"data-toggle="tooltip" data-placement="top" title="Export User"><span class="lnr lnr-download"></span></a>
                                <a class="btn btn-success" data-toggle="modal" data-target=".importModal"data-toggle="tooltip" data-placement="top" title="Import User"><span class="lnr lnr-upload"></span></a>
                                <a href="/user/export/template" class="btn btn-default"data-toggle="tooltip" data-placement="top" title="Download template"><span class="lnr lnr-text-align-justify"></span></a>
                            </div>
						</div>
                        <br><br><br>
						<div class="panel-body table-responsive">
							<table class="table table-hover">
								<thead>
                                <tr>
                                    <th>NO</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Area</th>
                                    <th>Badan Usaha</th>
                                    <th>Divisi</th>
                                    <th>Role</th>
                                    <th>Approval</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
								</thead>
								<tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><a href="#">{{ $user->fullname }}</a></td>
                                        <td><a href="#">{{ $user->username }}</a></td>
                                        <td>{{ optional(optional($user->division)->area)->area ?? 'N/A' }}</td>
                                        <td>{{ $user->badan_usaha->badan_usaha ?? 'N/A' }}</td>
                                        <td>{{ $user->division->division ?? 'N/A' }}</td>
                                        <td>{{ $user->role->role ?? 'N/A' }}</td>
                                        <td>{{ $user->approval->fullname ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            <a href="/user/{{$user->id}}/edit" class="btn btn-warning btn-xs"><span class="lnr lnr-pencil"></span></a>
                                            @if ($user->deleted_at)
                                                <a href="/user/{{ $user->id }}/active" class="btn btn-danger btn-xs"
                                                    onclick="return confirm('Mengaktifkan kembali user {{ $user->fullname }}?')"><span class="lnr lnr-cross-circle"></span></a>
                                            @else
                                                <a href="/user/{{$user->id}}/delete" class="btn btn-success btn-xs"
                                                    onclick="return confirm('Apalah anda yakin menonaktifkan user {{ $user->fullname }}?')"><span class="lnr lnr-checkmark-circle"></span></a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
								</tbody>
							</table>
                            <div style="float:right">
                                {{ $users->links() }}
                            </div>
						</div>
					</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="userModalLabel">Tambah User</h1>
                </div>
                <div class="modal-body">
                <form action="/user/create" method="POST" enctype="multipart/form-data">
                {{csrf_field()}}
                <div class="form-group">
                    <label for="inputNama" class="form-label">Nama Lengkap</label>
                    <input name="fullname" type="text" class="form-control" id="inputNama" placeholder="Nama Lengkap.." required>
                </div>
                <div class="form-group">
                    <label for="inputUser" class="form-label">Username</label>
                    <input name="username" type="text" class="form-control" id="inputUser" placeholder="Username.." required>
                </div>
                <div class="form-group">
                    <label for="inputPassword" class="form-label">Password</label>
                    <input name="password" type="text" class="form-control" id="inputPassword" placeholder="Password.." required>
                </div>
                <div class="form-group">
                    <label for="inputBU" class="form-label">Badan Usaha</label>
                        <select class="form-control" id="badan_usaha_id" name="badan_usaha_id" required>
                            <option selected disabled>-- Pilih Badan Usaha --</option>
                            @foreach ($badan_usahas as $badan_usaha)
                                <option value="{{ $badan_usaha->id }}">
                                    {{ $badan_usaha->badan_usaha }}</option>
                            @endforeach
                        </select>
                </div>
                <div class="form-group">
                    <label for="division_id" class="form-label">Divisi</label>
                        <select class="form-control" id="division_id" name="division_id" required>
                            <option selected disabled>-- Pilih Divisi --</option>
                            @foreach ($division as $division)
                                <option value="{{ $division->id }}">
                                    {{ $division->division }}</option>
                            @endforeach
                        </select>
                </div>
                <div class="form-group">
                    <label for="inputRole" class="form-label">Role</label>
                        <select class="form-control" id="role_id" name="role_id" required>
                            <option selected disabled>-- Pilih Role --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">
                                    {{ $role->role }}</option>
                            @endforeach
                        </select>
                </div>
                <div class="form-group">
                    <label for="inputApproval" class="form-label">Approval</label>
                        <select class="form-control" id="approval_id" name="approval_id" required>
                            <option selected disabled>-- Pilih Approval --</option>
                                @foreach ($approvals as $approval)
                                    <option value="{{ $approval->id }}">
                                        {{ $approval->fullname }}</option>
                                @endforeach
                        </select>
                </div>
                <div class="form-group">
                    <label for="inputProfile" class="form-label">Profil Picture</label>
                    <input type="file" name="profile_picture" class="form-control">
                </div>
                <br>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-primary">SIMPAN</button>
                </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <form action="/user/import" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal fade importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="importModalLabel">Import User</h1>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="">Pilih File</label>
                        <input type="file" class="form-control" name="fileImport">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-primary">IMPORT</button>
                </div>
            </form>
        </div>
    </div>
</div>
</form>
@stop