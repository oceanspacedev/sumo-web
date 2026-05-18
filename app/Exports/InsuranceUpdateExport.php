<?php

namespace App\Exports;

use App\Models\InsuranceUpdate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class InsuranceUpdateExport implements FromCollection, WithHeadings, WithMapping
{
    protected String $id;
    protected String $policy_number;

    function __construct(String $id, String $policy_number)
    {
        $this->id = $id;
        $this->policy_number = $policy_number;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $id = $this->id;

        return InsuranceUpdate::where('insurance_id',$id)->get();
    }

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

    public function map($insurance) : array
    {
        return [
            $this->policy_number,
            $insurance->policy_number,
            $insurance->stock_insurance_provider->insurance_provider,
            $insurance->stock_worth,
            $insurance->actual_stock_worth,
            $insurance->stock_premium,
            $insurance->building_insurance_provider->insurance_provider,
            $insurance->building_worth,
            $insurance->building_premium,
            Carbon::parse($insurance->join_date)->format('Y-m-d'),
            Carbon::parse($insurance->expired_date)->format('Y-m-d'),
            $insurance->notes,
        ];
    }
}
