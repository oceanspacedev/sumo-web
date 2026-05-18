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
                                <h3 class="panel-title">Data Barang</h3>
                            </div>
                            <div class="col-md-4 text-right">
                                <form class="form-inline" id="my_form" action="/product">
                                    <div class="form-group">
                                      <input type="text" class="form-control" name="search" placeholder="Enter your text">
                                      <a href="javascript:{}" onclick="document.getElementById('my_form').submit();" class="btn btn-info" ><span class="lnr lnr-magnifier"></span></a>
                                    </div>
                                  </form>
                            </div>
                            <div class="col-md-6 text-right">
                                <a class="btn btn-info" data-toggle="modal" data-target="#productModal" data-toggle="tooltip" data-placement="top" title="Tambah Barang"><span class="lnr lnr-plus-circle"></span></a>
                                <a href="/product/export" class="btn btn-primary" data-toggle="tooltip" data-placement="top" title="Export barang"><span class="lnr lnr-download"></span></a>
                                <a class="btn btn-success" data-toggle="modal" data-target=".importModal" data-toggle="tooltip" data-placement="top" title="Import barang"><span class="lnr lnr-upload"></span></a>
                                <a href="/product/export/template" class="btn btn-default" data-toggle="tooltip" data-placement="top" title="Download template"><span class="lnr lnr-text-align-justify"></span></a>
                            </div>                            
						</div>
                        <br><br><br>
						<div class="panel-body mt-4 table-responsive">
							<table class="table table-hover">
								<thead>
                                <tr>
                                    <th>NO</th>
                                    <th>Barang</th>
                                    <th>Kategori</th>
                                    <!-- <th>Stok</th> -->
                                    <th>Tipe Unit</th>
                                    <th>Harga</th>
                                    <th>Deskripsi</th>
                                    <th>Gambar</th>
                                    <th>Diupdate pada</th>
                                    <th>Aksi</th>
                                </tr>
								</thead>
								<tbody>
                                @foreach ($products as $product)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $product->product }}</td>
                                    <td>{{ $product->category->category }}</td>
                                    <!-- <td>{{ $product->stock }}</td> -->
                                    <td>{{ $product->unit_type->unit_type }}</td>
                                    <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                    <td>{{ $product->description }}</td>
                                    <td><img src="{{ $product->getProductImage() }}" class="img" width="100px" alt="Barang" data-toggle="modal" data-target="#imageModal"></td>
                                    <td>{{ $product->updated_at->formatLocalized('%A, %d %b %Y') }}</td>
                                    <td>
                                        <a href="/product/{{$product->id}}/edit" class="btn btn-warning btn-xs"><span class="lnr lnr-pencil"></span></a>
                                        <a href="#" data-toggle="modal" data-target="#qrModal{{$product->id}}" class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="top" title="Create QR Code"><i class="fa fa-qrcode"></i></a>
                                        <!-- <a href="/product/{{$product->id}}/delete" class="btn btn-danger btn-xs" onclick="return confirm('Yakin akan menghapus data ?')">Hapus</a> -->
                                    </td>

                                    <!-- Modal QR -->
                                    <div class="modal fade" id="qrModal{{$product->id}}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title" id="myModalLabel">QR Code</h4>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-12 text-right">
                                                            <a href="/printproductqr/{{$product->id}}" class="btn btn-info btn-sm"><span class="lnr lnr-printer"></span></a>
                                                        </div>
                                                        <br>
                                                        <div class="col-md-12 text-center">
                                                            <div style="display: inline-block; margin-bottom: 50px;">{!! DNS2D::getBarcodeHTML(strval($product->id), 'QRCODE') !!}</div>
                                                            <br><br>
                                                            <div style="display: inline-block;"><p style="color: black;">{{ $product->product }}</p></div>
                                                            <br>
                                                            <div style="display: inline-block;"><p style="color: black;">Harga: {{ $product->price }}</p></div>
                                                            <br>
                                                            <div style="display: inline-block;">{!! DNS1D::getBarcodeHTML(strval($product->id), 'C39') !!}</div>
                                                            <br>
                                                            <div style="display: inline-block;"><p style="color: black;">ID: {{ $product->id }}</p></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Image -->
                                    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content">
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-12 text-center">
                                                        <img id="modalImage" src="{{ $product->getProductImage() }}" alt="Large Image" style="width: 50%;">
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                                </tr>
                                @endforeach
								</tbody>
							</table>
                            <div style="float:right">
                                {{ $products->links() }}
                            </div>
						</div>
					</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" role="dialog" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="productModalLabel">Tambah Barang</h1>
                </div>
                <div class="modal-body">
                    <form action="/product/create" method="POST" enctype="multipart/form-data">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="inputProduct" class="form-label">Nama Barang</label>
                        <input name="product" type="text" class="form-control" id="inputProduct" placeholder="Nama barang.." required>
                    </div>
                    <div class="form-group">
                        <label for="inputCategory" class="form-label">Kategori</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option selected disabled>-- Pilih Kategori --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">
                                        {{ $category->category }}</option>
                                @endforeach
                            </select>
                    </div>
                    <div class="form-group">
                        <label for="inputUnitType" class="form-label">Tipe Unit</label>
                        <select class="form-control" id="unit_type_id" name="unit_type_id" required>
                                <option selected disabled>-- Pilih Tipe Unit --</option>
                                @foreach ($unit_types as $unit_type)
                                    <option value="{{ $unit_type->id }}">
                                        {{ $unit_type->unit_type }}</option>
                                @endforeach
                            </select>
                    </div>
                    <div class="form-group">
                        <label for="inputPrice" class="form-label">Harga</label>
                        <input name="price" type="number" class="form-control" id="inputPrice" placeholder="Harga..">
                    </div>
                    <div class="form-group">
                        <label for="inputDescription" class="form-label">Keterangan</label>
                        <input name="description" type="text" class="form-control" id="inputDescription" placeholder="Keterangan..">
                    </div>
                    <div class="form-group">
                        <label for="inputProductImage" class="form-label">Foto Barang</label>
                        <input type="file" name="product_image" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="inputStock" class="form-label">Stok</label>
                        <input name="stock" type="number" class="form-control" id="inputStock" placeholder="Stok..">
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

    <!-- Modal -->
    <form action="/product/import" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal fade importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="lnr lnr-cross"></i></button>
                    <h1 class="modal-title" id="importModalLabel">Import Data Barang</h1>
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