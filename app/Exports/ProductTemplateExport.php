<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductTemplateExport implements WithHeadings
{
    public function headings(): array
    {
        return [
            'nama_barang',
            'kategori',
            'tipe_unit',
            'harga',
            'keterangan',
            'stok',
        ];
    }
}
