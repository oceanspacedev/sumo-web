<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;

class RentTemplateExport implements WithHeadings
{
    public function headings(): array
    {
        return [
            'kode',
            'alamat_bangunan',
            'nama_bangunan',
            'pihak_pertama',
            'pihak_kedua',
            'sewa_per_tahun',
            'dana_cvcs',
            'dana_online',
            'tanggal_mulai',
            'tanggal_akhir',
            'bukti_potong',
            'berkas',
            'status',
            'reminder_bulan_sebelumnya',
            'catatan',
        ];
    }
}
