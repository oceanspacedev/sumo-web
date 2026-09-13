<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductTemplateExport implements Export, WithHeadings
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
