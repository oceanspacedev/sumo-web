<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\UnitType;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToModel, WithHeadingRow
{
     /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $product = new Product();
        $product = $product->where('product', strtolower($row['nama_barang']));
        if ($product->first()) {
            $category_id = Category::where('category', preg_replace('/\s+/', '', $row['kategori']))->first()->id;
            $unit_type_id = UnitType::where('unit_type', preg_replace('/\s+/', '', $row['tipe_unit']))->first()->id;
            $product->update([
                'product' => strtoupper($row['nama_barang']),
                'category_id' => $category_id,
                'unit_type_id' => $unit_type_id,
                'price' => $row['harga'] ?? $product->first()->price,
                'description' => $row['keterangan'] ?? $product->first()->description,
                'stock' => $row['stok'] ?? $product->first()->stock,
            ]);
        } else {
            $category_id = Category::where('category', preg_replace('/\s+/', '', $row['kategori']))->first()->id;
            $unit_type_id = UnitType::where('unit_type', preg_replace('/\s+/', '', $row['tipe_unit']))->first()->id;
            //dd($unit_type_id);
            return new Product([
                'product' => strtoupper($row['nama_barang']),
                'category_id' => $category_id,
                'unit_type_id' => $unit_type_id,
                'price' => $row['harga'],
                'description' => $row['keterangan'],
                'stock' => $row['stok'],
            ]);
        }
    }
}
