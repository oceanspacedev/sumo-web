<?php

namespace App\Imports;

use App\Models\Insurance;
use App\Models\InsuranceCategory;
use App\Models\InsuranceProvider;
use App\Models\InsuranceScope;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class InsuranceImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $user_id = Auth::user()->id;
        $insurance = new Insurance();
        $insurance = $insurance->where('policy_number', strtolower($row['no_polis']));
        if ($insurance->first()) {
            $stock_inprov_id = InsuranceProvider::where('insurance_provider', preg_replace('/\s+/', '', $row['asuransi_stok']))->first()->id ?? null;
            $building_inprov_id = InsuranceProvider::where('insurance_provider', preg_replace('/\s+/', '', $row['asuransi_bangunan']))->first()->id ?? null;
            $incategory_id = InsuranceCategory::where('insurance_category', preg_replace('/\s+/', '', $row['kategori_asuransi']))->first()->id;
            $inscope_id = InsuranceScope::where('insurance_scope', preg_replace('/\s+/', '', $row['cakupan_asuransi']))->first()->id;
            $insurance->update([
                'policy_number' => $row['no_polis'],
                'insured_address' => $row['alamat_tertanggung'],
                'insured_name' => $row['nama_tertanggung'],
                'warehouse_code' => $row['kode_gudang'],
                'insured_detail' => $row['detail_asuransi'],
                'risk_address' => $row['alamat_yang_diasuransikan'],
                'stock_inprov_id' => $stock_inprov_id,
                'building_inprov_id' => $building_inprov_id,
                'stock_worth' => $row['nilai_stok'],
                'actual_stock_worth' => $row['nilai_aktual_stok'],
                'stock_premium' => $row['premi_stok'],
                'building_worth' => $row['nilai_bangunan'],
                'building_premium' => $row['premi_bangunan'],
                'insurance_category_id' => $incategory_id,
                'join_date' => Carbon::createFromFormat('Y-m-d', $row['tanggal_mulai']),
                'expired_date' => Carbon::createFromFormat('Y-m-d', $row['tanggal_akhir']),
                'insurance_scope_id' => $inscope_id,
                'user_id' => $user_id,
                'notes' => $row['catatan'],
                'status' => $row['status'] ?? 'BERJALAN',
            ]);
        } else {
            $stock_inprov_id = InsuranceProvider::where('insurance_provider', preg_replace('/\s+/', '', $row['asuransi_stok']))->first()->id ?? null;
            $building_inprov_id = InsuranceProvider::where('insurance_provider', preg_replace('/\s+/', '', $row['asuransi_bangunan']))->first()->id ?? null;
            $incategory_id = InsuranceCategory::where('insurance_category', preg_replace('/\s+/', '', $row['kategori_asuransi']))->first()->id;
            $inscope_id = InsuranceScope::where('insurance_scope', preg_replace('/\s+/', '', $row['cakupan_asuransi']))->first()->id;
            return new insurance([
                'policy_number' => $row['no_polis'],
                'insured_address' => $row['alamat_tertanggung'],
                'insured_name' => $row['nama_tertanggung'],
                'warehouse_code' => $row['kode_gudang'],
                'insured_detail' => $row['detail_asuransi'],
                'risk_address' => $row['alamat_yang_diasuransikan'],
                'stock_inprov_id' => $stock_inprov_id,
                'building_inprov_id' => $building_inprov_id,
                'stock_worth' => $row['nilai_stok'],
                'actual_stock_worth' => ($row['nilai_aktual_stok'] !== null && $row['nilai_aktual_stok'] !== '') ? $row['nilai_aktual_stok'] : null,
                'stock_premium' => $row['premi_stok'],
                'building_worth' => $row['nilai_bangunan'],
                'building_premium' => $row['premi_bangunan'],
                'insurance_category_id' => $incategory_id,
                'join_date' => Carbon::createFromFormat('Y-m-d', $row['tanggal_mulai']),
                'expired_date' => Carbon::createFromFormat('Y-m-d', $row['tanggal_akhir']),
                'insurance_scope_id' => $inscope_id,
                'user_id' => $user_id,
                'notes' => $row['catatan'],
                'status' => $row['status'] ?? 'BERJALAN',
            ]);
        }
    }
}
