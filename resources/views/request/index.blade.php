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
                                    <li class="breadcrumb-item active" aria-current="page">Pengajuan</li>
                                </ol>
                                </nav>
                            </div>
                            <div class="col-md-12" style="margin-bottom: 30px;">
                                <!-- <div class="col-md-1" style="margin-bottom: 20px;">
                                    <h3 class="panel-title">Pengajuan</h3>
                                    <br>
                                </div> -->
                                <div class="col-md-4 text-right">
                                    <form class="form-inline" id="my_form" action="/request">
                                        <div class="form-group">
                                        <input type="text" class="form-control" name="search" id="tanggal-request" placeholder="Enter your text">
                                        <a href="javascript:{}" onclick="document.getElementById('my_form').submit();" class="btn btn-info" ><span class="lnr lnr-magnifier"></span></a>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-3 text-right">
                                    <form class="form-inline" id="inputStatusAkhir" action="/request">
                                        <div class="form-group">
                                        <select class="form-control" name="selectStatusAkhir">
                                            <option selected value="">-- Status Akhir --</option>
                                            <option value="undone">BELUM PROSES</option>
                                            <option value="0">MENUNGGU</option>
                                            <option value="3">DIPROSES</option>
                                            <option value="4">SELESAI</option>
                                            <option value="1">DITERIMA</option>
                                            <option value="2">DIBATALKAN</option>
                                        </select>
                                        <a href="javascript:{}" onclick="document.getElementById('inputStatusAkhir').submit();" class="btn btn-info" ><span class="lnr lnr-magnifier"></span></a>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-2 text-left">
                                    <form class="form-inline" id="code_form" action="/request">
                                        <div class="form-group">
                                        <input type="text" class="form-control" name="code" placeholder="Cari nama ..">
                                        <!-- <a href="javascript:{}" onclick="document.getElementById('code_form').submit();" class="btn btn-info" ><span class="lnr lnr-magnifier"></span></a> -->
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-2 text-right">
                                    @if (auth()->user()->role_id > 3)
                                        <a href="/request/create" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Tambah pengajuan"><span class="lnr lnr-plus-circle"></span></a>
                                    @endif
                                    @if (auth()->user()->role_id == 1 || (auth()->user()->role_id == 3 && auth()->user()->division_id == 6) || (auth()->user()->role_id == 3 && auth()->user()->division_id == 11))
                                        <a href="#exportRequest" data-toggle="modal" class="btn btn-primary" data-toggle="tooltip" data-placement="top" title="Export pengajuan"><span class="lnr lnr-download"></span></a>
                                        <a href="#exportQR" data-toggle="modal" class="btn btn-default" data-toggle="tooltip" data-placement="top" title="Export master QR pengajuan"><i class="fa fa-qrcode"></i></a>
                                    @endif
                                </div>
                            </div>
						</div>
						<div class="panel-body table-responsive">
                            <div class="col-md-12">
                                <table class="table table-hover" id="reqbar_table">
                                    <thead>
                                    <tr>
                                        <th>NO</th>
                                        <!-- <th>Kode</th> -->
                                        <th>Pemohon</th>
                                        <!-- <th>Divisi</th> -->
                                        <th>Diajukan pada</th>
                                        <th>Tipe Pengajuan</th>
                                        <!-- <th>Barang</th> -->
                                        <th>Lampiran</th>
                                        <th>Lampiran 2</th>
                                        <th>Status PO</th>
                                        <th>Disetujui oleh</th>
                                        <th>Disetujui pada</th>
                                        <th>Diproses oleh</th>
                                        <!-- <th>Diproses pada</th> -->
                                        <th>Status Akhir</th>
                                        <th>Aksi</th>
                                        <th></th> 
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($requestBarangs as $reqbar)
                                    <tr>
                                        <!-- NO -->
                                        <td>{{ $loop->iteration }}</td>
                                        <!-- KODE -->
                                        <!-- <td>{{ $reqbar->request_code }}</td> -->
                                        <!-- PEMOHON -->
                                        <td>{{ $reqbar->user->fullname }}</td>
                                        <!-- DIVISI -->
                                        <!-- <td>{{ $reqbar->user->division->division }}</td> -->
                                        <!-- DIAJUKAN PADA -->
                                        <td>{{ Carbon\Carbon::parse($reqbar->date)->format('d M Y H:i') }}</td>
                                        <!-- TIPE PENGAJUAN -->
                                        <td>{{ $reqbar->request_type->request_type }}</td>
                                        <!-- <td>{{ $reqbar->request_detail }}</td> -->
                                        <!-- LAMPIRAN -->
                                        @if (Storage::exists('public/Request_File/' . $reqbar->request_file) && Storage::size('public/Request_File/' . $reqbar->request_file) > 0)
                                            <td><a href="{{ asset('storage/Request_File/' . $reqbar->request_file) }}">Lihat Dokumen</a></td>
                                        @else
                                            <td>-</td>
                                        @endif
                                        <!-- LAMPIRAN 2 -->
                                        @if (Storage::exists('public/Request_File/' . $reqbar->request_file_2) && Storage::size('public/Request_File/' . $reqbar->request_file_2) > 0)
                                            <td><a href="{{ asset('storage/Request_File/' . $reqbar->request_file_2) }}">Lihat Dokumen</a></td>
                                        @else
                                            <td>-</td>
                                        @endif
                                        <!-- STATUS PO -->
                                        @foreach($reqbar->request_approval as $approval)
                                            @if ($approval->approval_type == 'ACCOUNTING' && $approval->approved_by == null)
                                                <td class={{ $reqbar->status_po == 0 ? "text-danger" : "text-success" }}>{{ $reqbar->status_po == 0 ? '' : 'YA' }}</td>
                                            @elseif ($approval->approval_type == 'ACCOUNTING' && $approval->approved_by != null)
                                                <td class={{ $reqbar->status_po == 0 ? "text-danger" : "text-success" }}>{{ $reqbar->status_po == 0 ? 'TIDAK' : 'YA' }}</td>
                                            @endif
                                        @endforeach
                                        <!-- DISETUJUI OLEH -->
                                        <td>
                                        @if($reqbar->request_type->id == 2 || $reqbar->request_type->id == 3)
                                            @foreach($reqbar->request_approval as $approval)
                                                @if ($approval->approval_type == 'MANAGER')
                                                    {{$approval->approved_by != null ? $approval->user->fullname : "-" }}
                                                @endif
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                        </td>
                                        <!-- DISETUJUI PADA -->
                                        <td>
                                        @if($reqbar->request_type->id == 2 || $reqbar->request_type->id == 3)
                                            @foreach($reqbar->request_approval as $approval)
                                                @if ($approval->approval_type == 'MANAGER')
                                                    {{$approval->approved_by != null ? Carbon\Carbon::parse($approval->approved_at)->format('d M Y H:i') : "-" }}
                                                @endif
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                        </td>
                                        <!-- DIPROSES OLEH -->
                                        <td>
                                            @foreach($reqbar->request_approval as $approval)
                                                @if ($approval->approval_type == 'EXECUTOR')
                                                    {{$approval->approved_by != null ? $approval->user->fullname : "-" }}
                                                @endif
                                            @endforeach
                                        </td>
                                        <!-- DIPROSES PADA -->
                                        <!-- STATUS AKHIR -->
                                        <td class="
                                                @if ($reqbar->status_client == '0')
                                                    {{"text-warning"}}
                                                @elseif ($reqbar->status_client == '1')
                                                    {{"text-success"}}
                                                @elseif ($reqbar->status_client == '3')
                                                    {{"text-info"}}
                                                @elseif ($reqbar->status_client == '4')
                                                    {{"text-info"}}
                                                @else
                                                    {{"text-danger"}}
                                                @endif
                                           ">
                                                @if ($reqbar->status_client == '0')
                                                    {{"MENUNGGU"}}
                                                @elseif ($reqbar->status_client == '1')
                                                    {{"DITERIMA"}}
                                                @elseif ($reqbar->status_client == '3')
                                                    {{"DIPROSES"}}
                                                @elseif ($reqbar->status_client == '4')
                                                    {{"SELESAI"}}
                                                @else
                                                    {{"DIBATALKAN"}}
                                                @endif
                                        </td>
                                        <!-- AKSI -->
                                        <td style="white-space: nowrap;">
                                            @switch(auth()->user()->role_id)
                                                @case(1)
                                                <a href="/request/{{$reqbar->id}}/delete" class="btn btn-danger btn-xs" type="button"><span class="lnr lnr-trash"></span></a>
                                                @case(3)
                                                    <!-- Button check kalau divisi Role Executor WHM, AUDIT, MKLI-HO -->
                                                    @if ((auth()->user()->division_id == 9 && $reqbar->request_type_id == 2) || (auth()->user()->division_id == 12 && $reqbar->request_type_id == 3) || (auth()->user()->division_id == 80 && $reqbar->request_type_id == 2))
                                                        @foreach($reqbar->request_approval as $approval)
                                                            @if ($approval->approval_type == 'MANAGER' && $approval->approved_by == null)
                                                                <a href="/request/{{$reqbar->id}}" class="btn btn-warning btn-xs" type="button">Check</a>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <!-- Sementara IT gabisa liat button edit -->
                                                        @if (auth()->user()->division_id != 11)
                                                            @if ($reqbar->status_client == 3)
                                                                <a href="/request/{{$reqbar->id}}/editStatus" class="btn btn-warning btn-xs" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a>
                                                            @else
                                                                @if ($reqbar->request_approval->where('approval_type', 'EXECUTOR')->whereNull('approved_by')->isNotEmpty() && $reqbar->request_type_id == 2 && !in_array($reqbar->user->division->area_id, [4, 5, 6, 11]))
                                                                    <a href="/request/{{$reqbar->id}}/editStatus" class="btn btn-warning btn-xs" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a>
                                                                @elseif ($reqbar->request_approval->where('approval_type', 'EXECUTOR')->whereNull('approved_by')->isNotEmpty() && $reqbar->request_type_id == 2 && in_array($reqbar->user->division->area_id, [4, 5, 6, 11]) && $reqbar->request_approval->where('approval_type', 'MANAGER')->whereNotNull('approved_by')->isNotEmpty())
                                                                    <a href="/request/{{$reqbar->id}}/editStatus" class="btn btn-warning btn-xs" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a>
                                                                @elseif ($reqbar->request_approval->where('approval_type', 'EXECUTOR')->whereNull('approved_by')->isNotEmpty() && $reqbar->request_type_id == 3)
                                                                    @if ($reqbar->request_approval->where('approval_type', 'MANAGER')->whereNotNull('approved_by')->isNotEmpty())
                                                                        <a href="/request/{{$reqbar->id}}/editStatus" class="btn btn-warning btn-xs" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a>
                                                                    @endif
                                                                @elseif ($reqbar->request_approval->where('approval_type', 'EXECUTOR')->whereNull('approved_by')->isNotEmpty() && $reqbar->request_approval->where('approval_type', 'ACCOUNTING')->whereNotNull('approved_by')->isNotEmpty() && $reqbar->request_type_id == 1)
                                                                    <a href="/request/{{$reqbar->id}}/editStatus" class="btn btn-warning btn-xs" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a>
                                                                @endif
                                                            @endif
                                                        @endif
                                                    @endif
                                                    @break
                                            
                                                @case(2)
                                                    @foreach($reqbar->request_approval as $approval)
                                                        @if ($approval->approval_type == 'ACCOUNTING' && $approval->approved_by == null && $reqbar->status_client != 2)
                                                            <a href="/request/{{$reqbar->id}}/editStatusAcc" class="btn btn-warning btn-xs" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a>
                                                        @endif
                                                    @endforeach
                                                    @break
    
                                                @default
                                                    @if ($reqbar->request_approval->where('approval_type', 'EXECUTOR')->whereNotNull('approved_by')->isNotEmpty() && $reqbar->request_approval->where('approval_type', 'ENDUSER')->whereNull('approved_by')->isNotEmpty() && ($reqbar->status_client == 4 || $reqbar->status_client == 0))
                                                    <a href="/request/{{$reqbar->id}}/editStatusClient" class="btn btn-warning btn-xs" data-toggle="modal" type="button"><span class="lnr lnr-pencil"></span></a>
                                                    @endif
                                                    @if ($reqbar->request_approval->where('approval_type', 'MANAGER')->whereNull('approved_by')->isNotEmpty() && $reqbar->request_approval->where('approval_type', 'EXECUTOR')->whereNull('approved_by')->isNotEmpty() && $reqbar->status_client != 2)
                                                    <a href="/request/{{$reqbar->id}}/cancelRequest" class="btn btn-danger btn-xs" data-toggle="modal" type="button" onclick="return confirm('Yakin akan membatalkan pengajuan ?')">Batal</a>
                                                    @endif
                                            @endswitch
                                        </td>  
                                        <td>
                                            <a href="/request/{{$reqbar->id}}" class="btn btn-default btn-xs" type="button"><span class="lnr lnr-eye"></span></a>
                                        </td>
                                        <td>
                                            @switch(auth()->user()->role_id)
                                                @case(1)
                                                @case(3)
                                                        @if (auth()->user()->division_id != 11)
                                                            @if ($reqbar->status_client == 1)
                                                                 <form class="form-inline" action="/request/{{$reqbar->id}}/editApplicant">
                                                                    <div class="form-group">
                                                                    <select class="form-control" id="selectEdit" name="selectEdit" onchange="this.form.submit();">
                                                                        <option selected value="">- Lainnya -</option>
                                                                        <option value="editApplicant">Ubah Pemohon</option>
                                                                    </select>
                                                                    </div>
                                                                </form>
                                                            @else
                                                            @endif
                                                        @endif
                                                    @break
                                                @default
                                                    @break
                                            @endswitch
                                        </td>
                                    </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div style="float:right">
                                {{ $requestBarangs->appends(Request::except('page'))->links() }}
                            </div>
						</div>
					</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Create -->
    <div class="modal fade" id="addRequestBarangModal" role="dialog" aria-labelledby="addRequestBarangModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="addRequestBarangModalLabel">Tambah Request</h1>
                </div>
                <div class="modal-body">
                    <form action="/request/store" method="POST" enctype="multipart/form-data">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="inputUser" class="form-label hidden">User</label>
                        <input name="user_id" type="hidden" class="form-control" id="inputUser" value="{{ auth()->user()->id }}">
                    </div>
                    <div class="form-group">
                        <label for="request_type_id" class="form-label">Tipe Request</label>
                        <select class="form-control" id="request_type_id" name="request_type_id" required>
                            <option selected disabled>-- Pilih Tipe Request --</option>
                            @foreach ($request_types as $reqtype)
                                <option value="{{ $reqtype->id }}">
                                    {{ $reqtype->request_type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product_id" class="form-label">Barang</label>
                        <select class="form-control" id="product_id" name="product_id" required>
                            <option selected disabled>-- Pilih Barang --</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->product }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inputQtyRequest" class="form-label">Kuantitas Request</label>
                        <input name="qty_request" type="number" class="form-control" id="inputQtyRequest" placeholder="Kuantitas Request.." required>
                    </div>
                    <div class="form-group">
                        <label for="inputQtyRemaining" class="form-label">Kuantitas Tersisa Saat Ini</label>
                        <input name="qty_remaining" type="number" class="form-control" id="inputQtyRemaining" placeholder="Sisa.." required>
                    </div>
                    <div class="form-group">
                        <label for="inputDescription" class="form-label">Deskripsi Pengajuan</label>
                        <input name="description" type="text" class="form-control" id="inputDescription" placeholder="Deskripsi.." required>
                    </div>
                    <div class="form-group">
                    <label for="inputRequestFile" class="form-label">File Pengajuan</label>
                    <input type="file" name="request_file" class="form-control" required>
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
                    <!-- id nya salah -->
                    <form action="/request/{$requestBarang->id}/updateStatusClient" method="POST">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="dataid" class="form-label">ID Pelaporan</label>
                        <input type="text" name="dataid" id="dataid" class="form-control" readonly/>
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
                     <!-- id nya salah -->
                    <form action="/request/{$requestBarang->id}/updateStatus" method="POST">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="requestId" class="form-label">ID Pelaporan</label>
                        <input type="text" name="requestId" id="requestId" class="form-control" readonly/>
                    </div>
                    <div class="form-group">
                        <label for="inputStatus" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option selected value="PENDING">PENDING</option>
                                <option value="CLOSED">CLOSED</option>
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
    <form action="request/export" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal fade" id="exportRequest" aria-labelledby="exportRequesttLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="exportRequesttLabel">Export Laporan Pengajuan</h3>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="inputRequestType" class="form-label">Tipe Pengajuan</label>
                                <select class="form-control" id="request_type_id" name="request_type_id">
                                   <option selected disabled>-- Pilih Tipe Pengajuan --</option>
                                    @foreach ($request_types as $reqtype)
                                        <option value="{{ $reqtype->id }}">
                                            {{ $reqtype->request_type }}</option>
                                    @endforeach
                                </select>
                        </div>
                        <div class="form-group">
                            <label for="inputArea" class="form-label">Area</label>
                                <select class="form-control" id="area_id" name="area_id">
                                   <option selected disabled>-- Pilih Area --</option>
                                    @foreach ($areas as $area)
                                        <option value="{{ $area->id }}">
                                            {{ $area->area }}</option>
                                    @endforeach
                                </select>
                        </div>
                        <div class="form-group">
                            <label for="date" class="form-label">Tanggal</label>
                                <form action="request">
                                    <input type="text" class="form-control float-right" value="" name="exportRequest"
                                        id="tanggal-export-request" required>
                                </form>
                        </div>
                        <div class="form-group">
                            <label for="date" class="form-label">Filter</label>
                                <select class="form-control" name="selectFilterRequest">
                                    <option selected value="">-- Filter --</option>
                                    <option value="0">BELUM PROSES</option>
                                    <option value="1">SUDAH PROSES</option>
                                    <option value="5">SELESAI</option>
                                    <option value="4">DIBATALKAN</option>
                                    <!-- <option value="3">TANPA YG DIBATALKAN</option> -->
                                    <option value="2">SEMUA</option>
                                </select>
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

    <!-- Modal Export QR -->
    <form action="request/exportMasterQR" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal fade" id="exportQR" aria-labelledby="exportQRtLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="exportQRtLabel">Export ID Pengajuan</h3>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="inputRequestType" class="form-label">Tipe Pengajuan</label>
                                <select class="form-control" id="request_type_id" name="request_type_id">
                                   <option selected disabled>-- Pilih Tipe Pengajuan --</option>
                                    @foreach ($request_types as $reqtype)
                                        <option value="{{ $reqtype->id }}">
                                            {{ $reqtype->request_type }}</option>
                                    @endforeach
                                </select>
                        </div>
                        <div class="form-group">
                            <label for="date" class="form-label">Tanggal</label>
                                <form action="request">
                                    <input type="text" class="form-control float-right" value="" name="exportQR"
                                        id="tanggal-export-qr" required>
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