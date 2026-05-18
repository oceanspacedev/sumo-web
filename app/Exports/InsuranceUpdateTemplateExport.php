<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;

class InsuranceUpdateTemplateExport implements WithHeadings
{
    public function headings(): array
    {
        return [
            'no_polis_induk',
            'no_polis',
            'asuransi_stok',
            'nilai_stok',
            'nilai_aktual_stok',
            'premi_stok',
            'asuransi_bangunan',
            'nilai_bangunan',
            'premi_bangunan',
            'tanggal_mulai',
            'tanggal_akhir',
            'catatan',
        ];
    }
}
