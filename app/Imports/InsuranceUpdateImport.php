<?php

namespace App\Imports;

use App\Models\Insurance;
use App\Models\InsuranceUpdate;
use App\Models\InsuranceProvider;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class InsuranceUpdateImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $user_id = Auth::user()->id;
        $insurance = Insurance::where('policy_number', strtolower($row['no_polis_induk']))->first();

        if ($insurance) {
            $insuranceUpdate = InsuranceUpdate::where('policy_number', $row['no_polis'])
                ->where('insurance_id', $insurance->id)
                ->first();

            if (!$insuranceUpdate) {
                $insuranceUpdate = new InsuranceUpdate();
                $insuranceUpdate->policy_number = $row['no_polis'];
                $insuranceUpdate->insurance_id = $insurance->id;
            }

            $stock_inprov_id = InsuranceProvider::where('insurance_provider', preg_replace('/\s+/', '', $row['asuransi_stok']))->first()->id ?? null;
            $building_inprov_id = InsuranceProvider::where('insurance_provider', preg_replace('/\s+/', '', $row['asuransi_bangunan']))->first()->id ?? null;

            $insuranceUpdate->stock_inprov_id = $stock_inprov_id;
            $insuranceUpdate->building_inprov_id = $building_inprov_id;
            $insuranceUpdate->stock_worth = $row['nilai_stok'];
            $insuranceUpdate->actual_stock_worth = ($row['nilai_aktual_stok'] !== null && $row['nilai_aktual_stok'] !== '') ? $row['nilai_aktual_stok'] : null;
            $insuranceUpdate->stock_premium = $row['premi_stok'];
            $insuranceUpdate->building_worth = $row['nilai_bangunan'];
            $insuranceUpdate->building_premium = $row['premi_bangunan'];
            $insuranceUpdate->join_date = Carbon::createFromFormat('Y-m-d', $row['tanggal_mulai']);
            $insuranceUpdate->expired_date = Carbon::createFromFormat('Y-m-d', $row['tanggal_akhir']);
            $insuranceUpdate->user_id = $user_id;
            $insuranceUpdate->notes = $row['catatan'];
            $insuranceUpdate->status = $row['status'] ?? 'BERJALAN';

            $insuranceUpdate->save();
        }
        //else belum ada no_polis_induk nya
    }
}
