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
        if (empty($row['nama_barang'])) {
            return null;
        }

        $productName = strtoupper($row['nama_barang']);
        $category_id = $this->requiredLookupId(Category::class, 'category', $row['kategori'] ?? null, 'Kategori', $productName);
        $unit_type_id = $this->requiredLookupId(UnitType::class, 'unit_type', $row['tipe_unit'] ?? null, 'Tipe unit', $productName);

        $product = Product::where('product', strtolower($row['nama_barang']))->first();

        if ($product) {
            $product->update([
                'product' => $productName,
                'category_id' => $category_id,
                'unit_type_id' => $unit_type_id,
                'price' => $row['harga'] ?? $product->price,
                'description' => $row['keterangan'] ?? $product->description,
                'stock' => $row['stok'] ?? $product->stock,
            ]);
        } else {
            return new Product([
                'product' => $productName,
                'category_id' => $category_id,
                'unit_type_id' => $unit_type_id,
                'price' => $row['harga'] ?? null,
                'description' => $row['keterangan'] ?? null,
                'stock' => $row['stok'] ?? null,
            ]);
        }
    }

    private function requiredLookupId(string $model, string $column, ?string $value, string $label, string $productName): int
    {
        $normalized = preg_replace('/\s+/', '', trim((string) $value));

        if ($normalized === '') {
            throw new \InvalidArgumentException($label.' wajib diisi untuk barang '.$productName.'.');
        }

        $id = $model::where($column, $normalized)->value('id');

        if (! $id) {
            throw new \InvalidArgumentException($label.' "'.$value.'" tidak ditemukan untuk barang '.$productName.'.');
        }

        return $id;
    }
}
