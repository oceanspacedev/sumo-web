<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;

class InsuranceTemplateExport implements WithHeadings
{
    public function headings(): array
    {
        return [
            'no_polis',
            'alamat_tertanggung',
            'nama_tertanggung',
            'kode_gudang',
            'detail_asuransi',
            'alamat_yang_diasuransikan',
            'asuransi_stok',
            'nilai_stok',
            'nilai_aktual_stok',
            'premi_stok',
            'asuransi_bangunan',
            'nilai_bangunan',
            'premi_bangunan',
            'kategori_asuransi',
            'tanggal_mulai',
            'tanggal_akhir',
            'cakupan_asuransi',
            'status',
            'catatan',
        ];
    }
}
