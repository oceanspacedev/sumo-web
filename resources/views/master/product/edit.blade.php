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
                            <form action="/product/{{$product->id}}/update" method="POST" enctype="multipart/form-data">
                                {{csrf_field()}}
                                <div class="form-group">
                                    <label for="inputProduct" class="form-label">Nama Barang</label>
                                    <input name="product" type="text" class="form-control" id="inputProduct" value="{{$product->product}}" required>
                                </div>
                                <div class="form-group">
                                    <label for="inputCategory" class="form-label">Kategori</label>
                                        <select class="form-control" id="category_id" name="category_id" required>
                                            <option selected disabled>-- Pilih Kategori --</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" 
                                                    {{ $category->id === $product->category_id ? 'selected' : '' }}>
                                                    {{ $category->category }}</option>
                                            @endforeach
                                        </select>
                                </div>
                                <div class="form-group">
                                    <label for="inputUnitType" class="form-label">Tipe Unit</label>
                                        <select class="form-control" id="unit_type_id" name="unit_type_id" required>
                                            <option selected disabled>-- Pilih Tipe Unit --</option>
                                            @foreach ($unit_types as $unit_type)
                                                <option value="{{ $unit_type->id }}" 
                                                    {{ $unit_type->id === $product->unit_type_id ? 'selected' : '' }}>
                                                    {{ $unit_type->unit_type }}</option>
                                            @endforeach
                                        </select>
                                </div>
                                <div class="form-group">
                                    <label for="inputPrice" class="form-label">Harga</label>
                                    <input name="price" type="text" class="form-control" id="inputPrice" value="{{$product->price}}">
                                </div>
                                <div class="form-group">
                                    <label for="inputDescription" class="form-label">Keterangan</label>
                                    <input name="description" type="text" class="form-control" id="inputDescription" value="{{$product->description}}">
                                </div>
                                <div class="form-group">
                                    <label for="inputProductImage" class="form-label">Foto Barang</label>
                                    <input type="file" name="product_image" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="inputStock" class="form-label">Stok</label>
                                    <input name="stock" type="text" class="form-control" id="inputStock" value="{{$product->stock}}">
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