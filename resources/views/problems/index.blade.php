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
                                        <li class="breadcrumb-item active" aria-current="page">Pelaporan</li>
                                    </ol>
                                    </nav>
                                </div>
                                <div class="col-md-12" style="margin-bottom: 30px;">
                                    <div class="col-md-5 text-right">
                                        <form class="form-inline" id="my_form" action="/problemReport">
                                            <div class="form-group">
                                            <input type="text" class="form-control" name="search" id="tanggal-problem" placeholder="Enter your text">
                                            <a href="javascript:{}" onclick="document.getElementById('my_form').submit();" class="btn btn-info" ><span class="lnr lnr-magnifier"></span></a>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-3 text-right">
                                        <form class="form-inline" id="inputStatusAkhir" action="/problemReport">
                                            <div class="form-group">
                                            <select class="form-control" name="selectStatusAkhir">
                                                <option selected value="">-- Status Akhir --</option>
                                                <option value="0">MENUNGGU</option>
                                                <option value="1">DITERIMA</option>
                                                <option value="2">DIBATALKAN</option>
                                            </select>
                                            <a href="javascript:{}" onclick="document.getElementById('inputStatusAkhir').submit();" class="btn btn-info" ><span class="lnr lnr-magnifier"></span></a>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-2 text-left">
                                        <form class="form-inline" id="code_form" action="/problemReport">
                                            <div class="form-group">
                                            <input type="text" class="form-control" name="code" placeholder="Cari nama ..">
                                            <!-- <a href="javascript:{}" onclick="document.getElementById('code_form').submit();" class="btn btn-info" ><span class="lnr lnr-magnifier"></span></a> -->
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-2 text-right">
                                        @if (auth()->user()->role_id > 3)
                                            <a href="/problemReport/create" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Tambah laporan"><span class="lnr lnr-plus-circle"></span></a>
                                        @endif
                                        @if (auth()->user()->role_id == 1 || (auth()->user()->role_id == 3 && auth()->user()->division_id == 6) || (auth()->user()->role_id == 3 && auth()->user()->division_id == 11))
                                            <a href="#exportProblemReport" data-toggle="modal" class="btn btn-primary" data-toggle="tooltip" data-placement="top" title="Export laporan"><span class="lnr lnr-download"></span></a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>NO</th>
                                        <!-- <th>Kode</th> -->
                                        <th>Pelapor</th>
                                        <!-- <th>Divisi</th> -->
                                        <th>Tanggal</th>
                                        <th>Kategori</th>
                                        <th>Detail</th>
                                        <th>Foto Sebelum</th>
                                        <th>Status Pengerjaan</th>
                                        <th>Penjadwalan</th>
                                        <th>Diproses oleh</th>
                                        <th>Diproses pada</th>
                                        <th>Hasil Pengerjaan</th>
                                        <th>Foto Sesudah</th>
                                        <th>Status Akhir</th>
                                        <th> @if (auth()->user()->role_id == 4) Aksi @endif</th>
                                        <th> @if (auth()->user()->role_id != 4) Aksi @endif </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                       @foreach ($problems as $problem)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <!-- <td>{{ $problem->problem_report_code }}</td> -->
                                            <td>{{ $problem->user->fullname }}</td>
                                            <td>{{ Carbon\Carbon::parse($problem->date)->format('d M Y H:i') }}</td>
                                            <!-- <td>{{ $problem->title }}</td> -->
                                            <td>{{ $problem->prcategory->problem_report_category }}</td>
                                            <td>{{ $problem->description }}</td>
                                            <!-- PHOTO BEFORE -->
                                            @if (Storage::exists('public/Problem_Report_File/' . $problem->photo_before) && Storage::size('public/Problem_Report_File/' . $problem->photo_before) > 0)
                                                <td><a href="{{ asset('storage/Problem_Report_File/' . $problem->photo_before) }}">Lihat</a></td>
                                            @else
                                                <td>Tidak ada file</td>
                                            @endif
                                            <td class={{ $problem->status == 'PENDING' ? "text-warning" : ($problem->status == 'CANCELLED' ? "text-danger" : "text-success") }}>{{ $problem->status == 'PENDING' ? 'MENUNGGU' : ( $problem->status == 'CLOSED' ? 'SELESAI' : 'DIBATALKAN' ) }}</td>
                                            <td>{{ $problem->scheduled_at == null ? '' : Carbon\Carbon::parse($problem->scheduled_at)->format('d M Y') }}</td>
                                            <td>{{ $problem->closed_by == null ? '' : $problem->closedby->fullname }}</td>
                                            <td>{{ $problem->closed_at == null ? '' : Carbon\Carbon::parse($problem->closed_at)->format('d M Y H:i') }}</td>
                                            <td>{{ $problem->result_desc }}</td>
                                            <!-- PHOTO AFTER -->
                                            @if (Storage::exists('public/Problem_Report_File/' . $problem->photo_after) && Storage::size('public/Problem_Report_File/' . $problem->photo_after) > 0)
                                                <td><a href="{{ asset('storage/Problem_Report_File/' . $problem->photo_after) }}">Lihat</a></td>
                                            @else
                                                <td>Tidak ada file</td>
                                            @endif
                                            <td class={{ $problem->status_client == 0 ? "text-warning" : "text-success" }}> {{ $problem->status_client == 0 ? 'MENUNGGU' : 'SELESAI' }}</td>
                                            <td>
                                                @if (auth()->user()->role_id == 4 && $problem->status == 'CLOSED' && $problem->status_client != 1)
                                                <a href="/problemReport/{{$problem->id}}/editStatusClient" class="btn btn-warning btn-xs" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a>
                                                @endif
                                            </td>
                                            <td>
                                                @if (auth()->user()->role_id != 4 && (($problem->status != 'CLOSED' && $problem->status != 'CANCELLED') || $problem->status_client != 1))
                                                <a href="/problemReport/{{$problem->id}}/editStatus" class="btn btn-warning btn-xs" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a>
                                                @endif
                                            </td>
                                        </tr>
                                       @endforeach
                                    </tbody>
                                </table>
                                <div style="float:right">
                                    {{ $problems->appends(Request::except('page'))->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Create -->
    <div class="modal fade" id="addProblemsModal" role="dialog" aria-labelledby="addProblemsModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="addProblemsModalLabel">Tambah Pelaporan</h1>
                </div>
                <div class="modal-body">
                    <form action="/problemReport/store" method="POST">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="inputUser" class="form-label hidden">User</label>
                        <input name="user_id" type="hidden" class="form-control" id="inputUser" value="{{ auth()->user()->id }}">
                    </div>
                    <!-- <div class="form-group">
                        <label for="inputTitle" class="form-label">Judul Pelaporan</label>
                        <input name="title" type="text" class="form-control" id="inputTitle" placeholder="Judul.." required>
                    </div> -->
                    <div class="form-group">
                    <label for="inputPRCategory" class="form-label">Kategori Problem Report</label>
                        <select class="form-control" id="pr_category_id" name="pr_category_id" required>
                            <option selected disabled>-- Pilih Jenis Gangguan --</option>
                            @foreach ($prcategories as $prcategory)
                                <option value="{{ $prcategory->id }}">
                                    {{ $prcategory->problem_report_category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inputDescription" class="form-label">Deskripsi Pelaporan</label>
                        <input name="description" type="text" class="form-control" id="inputDescription" placeholder="Deskripsi.." required>
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

    <!-- Modal Edit Status Client-->
    <div class="modal fade" id="editStatusClientModal" role="dialog" aria-labelledby="editStatusClientModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="editStatusClientModalLabel">Ubah Status by Client</h1>
                </div>
                <div class="modal-body">
                    <form action="#" method="POST">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="problem_report_id" class="form-label">ID Pelaporan</label>
                        <input type="text" name="problem_report_id" id="problem_report_id" class="form-control" readonly/>
                    </div>
                    <div class="form-group">
                        <label for="description" class="form-label">Detail Pelaporan</label>
                        <!-- belum ada valuenya -->
                        <input type="text" name="description" id="description" class="form-control" readonly/>
                    </div>
                    <div class="form-group">
                        <label for="inputStatus" class="form-label">Status Client</label>
                            <select class="form-control" id="status_client" name="status_client">
                                <option selected value="0">PENDING</option>
                                <option value="1">CLOSED</option>
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
        </div>
    </div>

    <!-- Modal Edit Status -->
    <div class="modal fade" id="editStatusModal" role="dialog" aria-labelledby="editStatusModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="editStatusModalLabel">Ubah Status</h1>
                </div>
                <div class="modal-body">
                    <form action="#" method="POST">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="problem_report_id" class="form-label">ID Pelaporan</label>
                        <!-- belum ada valuenya -->
                        <input type="text" name="problem_report_id" id="problem_report_id" class="form-control" readonly/>
                    </div>
                    <!-- belum ada if scheduled_at -->
                    <div class="form-group">
                        <label for="inputScheduledAt" class="form-label">Penjadwalan</label>
                        <input type="text" class="form-control float-right" value="" name="scheduled_at" id="tanggalScheduled" required>
                    </div>
                    <div class="form-group">
                        <label for="inputStatus" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option selected value="PENDING">PENDING</option>
                                <option value="CLOSED">CLOSED</option>
                                <option value="CANCELLED">CANCELLED</option>
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
        </div>
    </div>

    <!-- Modal Export -->
    <form action="problemReport/export" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal fade" id="exportProblemReport" aria-labelledby="exportProblemReporttLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="exportProblemReporttLabel">Export Laporan Gangguan</h3>
                    </div>
                    <div class="modal-body">
                        <label for="date" class="form-label">Tanggal</label>
                            <div class="form-group">
                                <form action="problemReport">
                                    <input type="text" class="form-control float-right" value="" name="exportProblemReport"
                                        id="tanggal-problem-report" required>
                                </form>
                            </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Export</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop