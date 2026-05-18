<?php

namespace App\Exports;

use App\Models\Insurance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class InsuranceExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Insurance::with([
                'stock_insurance_provider',
                'building_insurance_provider',
                'insurance_category',
                'insurance_scope',
            ])->get();
    }

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
            'catatan',
        ];
    }

    public function map($insurance) : array
    {
        return [
            $insurance->policy_number,
            $insurance->insured_address,
            $insurance->insured_name,
            $insurance->warehouse_code,
            $insurance->insured_detail,
            $insurance->risk_address,
            $insurance->stock_insurance_provider->insurance_provider ?? '',
            $insurance->stock_worth,
            $insurance->actual_stock_worth,
            $insurance->stock_premium,
            $insurance->building_insurance_provider->insurance_provider ?? '',
            $insurance->building_worth,
            $insurance->building_premium,
            $insurance->insurance_category->insurance_category,
            Carbon::parse($insurance->join_date)->format('Y-m-d'),
            Carbon::parse($insurance->expired_date)->format('Y-m-d'),
            $insurance->insurance_scope->insurance_scope,
            $insurance->notes,
        ];
    }
}
