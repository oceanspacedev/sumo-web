<?php

namespace App\Exports;

use App\Models\Rent;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class RentExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Rent::all();
    }

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

    public function map($rent) : array
    {
        return [
            $rent->rent_code,
            $rent->rented_address,
            $rent->rented_detail,
            $rent->first_party,
            $rent->second_party,
            $rent->rent_per_year,
            $rent->cvcs_fund,
            $rent->online_fund,
            Carbon::parse($rent->join_date)->format('Y-m-d'),
            Carbon::parse($rent->expired_date)->format('Y-m-d'),
            $rent->deduction_evidence,
            $rent->document,
            $rent->status,
            $rent->month_before_reminder,
            $rent->notes,
        ];
    }
}
