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
        if (empty($row['no_polis'])) {
            return null;
        }

        $policyNumber = trim((string) $row['no_polis']);
        $user_id = Auth::id();
        $stock_inprov_id = InsuranceProvider::where('insurance_provider', preg_replace('/\s+/', '', $row['asuransi_stok'] ?? ''))->value('id');
        $building_inprov_id = InsuranceProvider::where('insurance_provider', preg_replace('/\s+/', '', $row['asuransi_bangunan'] ?? ''))->value('id');
        $incategory_id = $this->requiredLookupId(InsuranceCategory::class, 'insurance_category', $row['kategori_asuransi'] ?? null, 'Kategori asuransi', $policyNumber);
        $inscope_id = $this->requiredLookupId(InsuranceScope::class, 'insurance_scope', $row['cakupan_asuransi'] ?? null, 'Cakupan asuransi', $policyNumber);

        $insurance = $this->resolveInsurance($row, $policyNumber);
        $joinDate = $this->parseDate($row['tanggal_mulai'] ?? null, 'tanggal_mulai', $policyNumber, ! $insurance);
        $expiredDate = $this->parseDate($row['tanggal_akhir'] ?? null, 'tanggal_akhir', $policyNumber, ! $insurance);

        if ($insurance) {
            $insurance->update([
                'policy_number' => $row['no_polis'],
                'insured_address' => $row['alamat_tertanggung'] ?? $insurance->insured_address,
                'insured_name' => $row['nama_tertanggung'] ?? $insurance->insured_name,
                'warehouse_code' => $row['kode_gudang'] ?? $insurance->warehouse_code,
                'insured_detail' => $row['detail_asuransi'] ?? $insurance->insured_detail,
                'risk_address' => $row['alamat_yang_diasuransikan'] ?? $insurance->risk_address,
                'stock_inprov_id' => $stock_inprov_id,
                'building_inprov_id' => $building_inprov_id,
                'stock_worth' => $row['nilai_stok'] ?? $insurance->stock_worth,
                'actual_stock_worth' => $row['nilai_aktual_stok'] ?? $insurance->actual_stock_worth,
                'stock_premium' => $row['premi_stok'] ?? $insurance->stock_premium,
                'building_worth' => $row['nilai_bangunan'] ?? $insurance->building_worth,
                'building_premium' => $row['premi_bangunan'] ?? $insurance->building_premium,
                'insurance_category_id' => $incategory_id,
                'join_date' => $joinDate ?? $insurance->join_date,
                'expired_date' => $expiredDate ?? $insurance->expired_date,
                'insurance_scope_id' => $inscope_id,
                'user_id' => $user_id,
                'notes' => $row['catatan'] ?? $insurance->notes,
                'status' => $row['status'] ?? 'BERJALAN',
            ]);
        } else {
            return new Insurance([
                'policy_number' => $row['no_polis'],
                'insured_address' => $row['alamat_tertanggung'] ?? null,
                'insured_name' => $row['nama_tertanggung'] ?? null,
                'warehouse_code' => $row['kode_gudang'] ?? null,
                'insured_detail' => $row['detail_asuransi'] ?? null,
                'risk_address' => $row['alamat_yang_diasuransikan'] ?? null,
                'stock_inprov_id' => $stock_inprov_id,
                'building_inprov_id' => $building_inprov_id,
                'stock_worth' => $row['nilai_stok'] ?? null,
                'actual_stock_worth' => (($row['nilai_aktual_stok'] ?? null) !== null && ($row['nilai_aktual_stok'] ?? '') !== '') ? $row['nilai_aktual_stok'] : null,
                'stock_premium' => $row['premi_stok'] ?? null,
                'building_worth' => $row['nilai_bangunan'] ?? null,
                'building_premium' => $row['premi_bangunan'] ?? null,
                'insurance_category_id' => $incategory_id,
                'join_date' => $joinDate,
                'expired_date' => $expiredDate,
                'insurance_scope_id' => $inscope_id,
                'user_id' => $user_id,
                'notes' => $row['catatan'] ?? null,
                'status' => $row['status'] ?? 'BERJALAN',
            ]);
        }
    }

    private function resolveInsurance(array $row, string $policyNumber): ?Insurance
    {
        $id = trim((string) ($row['id'] ?? ''));

        if ($id !== '') {
            if (! ctype_digit($id)) {
                throw new \InvalidArgumentException('ID asuransi tidak valid untuk polis '.$policyNumber.'.');
            }

            $insurance = Insurance::find((int) $id);

            if (! $insurance) {
                throw new \InvalidArgumentException('Data asuransi dengan ID '.$id.' tidak ditemukan untuk polis '.$policyNumber.'.');
            }

            return $insurance;
        }

        $matches = Insurance::where('policy_number', $policyNumber)->get();

        if ($matches->count() > 1) {
            throw new \InvalidArgumentException('Polis '.$policyNumber.' ditemukan pada '.$matches->count().' data asuransi. Isi kolom id dari export asuransi agar import tidak mengubah data yang salah.');
        }

        return $matches->first();
    }

    private function requiredLookupId(string $model, string $column, ?string $value, string $label, string $policyNumber): int
    {
        $normalized = preg_replace('/\s+/', '', trim((string) $value));

        if ($normalized === '') {
            throw new \InvalidArgumentException($label.' wajib diisi untuk polis '.$policyNumber.'.');
        }

        $id = $model::where($column, $normalized)->value('id');

        if (! $id) {
            throw new \InvalidArgumentException($label.' "'.$value.'" tidak ditemukan untuk polis '.$policyNumber.'.');
        }

        return $id;
    }

    private function parseDate($value, string $fieldName, string $policyNumber, bool $required = false): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        $dateValue = trim((string) $value);

        if ($dateValue === '') {
            if ($required) {
                throw new \InvalidArgumentException($fieldName.' wajib diisi untuk polis '.$policyNumber.'.');
            }

            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $dateValue);
            $errors = Carbon::getLastErrors();

            if (($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $dateValue) {
                throw new \InvalidArgumentException();
            }

            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \InvalidArgumentException($fieldName.' tidak valid untuk polis '.$policyNumber.'.');
        }
    }
}
