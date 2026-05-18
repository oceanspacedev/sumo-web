<?php

namespace App\Exports;

use App\Models\RentUpdate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class RentUpdateExport implements FromCollection, WithHeadings, WithMapping
{
    protected String $id;
    protected String $rent_code;

    function __construct(String $id, String $rent_code)
    {
        $this->id = $id;
        $this->rent_code = $rent_code;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $id = $this->id;

        return RentUpdate::where('rent_id',$id)->get();
    }

    public function headings(): array
    {
        return [
            'kode_sewa_induk',
            'kode',
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

    public function map($rentUpdate) : array
    {
        return [
            $this->rent_code,
            $rentUpdate->rent_code,
            $rentUpdate->first_party,
            $rentUpdate->second_party,
            $rentUpdate->rent_per_year,
            $rentUpdate->cvcs_fund,
            $rentUpdate->online_fund,
            Carbon::parse($rentUpdate->join_date)->format('Y-m-d'),
            Carbon::parse($rentUpdate->expired_date)->format('Y-m-d'),
            $rentUpdate->deduction_evidence,
            $rentUpdate->document,
            $rentUpdate->status,
            $rentUpdate->month_before_reminder,
            $rentUpdate->notes,
        ];
    }
}
