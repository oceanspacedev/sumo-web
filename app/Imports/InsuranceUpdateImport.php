<?php

namespace App\Imports;

use App\Models\Insurance;
use App\Models\InsuranceUpdate;
use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class InsuranceUpdateImport implements ToModel, WithHeadingRow
{
    private int $insuranceId;

    private string $parentPolicyNumber;

    public function __construct(Insurance $insurance)
    {
        $this->insuranceId = $insurance->id;
        $this->parentPolicyNumber = trim((string) $insurance->policy_number);
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row): ?Model
    {
        if (empty($row['no_polis_induk']) && empty($row['no_polis'])) {
            return null;
        }

        if (empty($row['no_polis_induk'])) {
            throw new \InvalidArgumentException('No polis induk wajib diisi untuk import update asuransi.');
        }

        if (empty($row['no_polis'])) {
            throw new \InvalidArgumentException('No polis update wajib diisi untuk import update asuransi.');
        }

        $rowParentPolicyNumber = trim((string) $row['no_polis_induk']);

        if (strcasecmp($rowParentPolicyNumber, $this->parentPolicyNumber) !== 0) {
            throw new \InvalidArgumentException('No polis induk "'.$row['no_polis_induk'].'" tidak sesuai dengan polis yang sedang dibuka ('.$this->parentPolicyNumber.').');
        }

        $user_id = Auth::id();
        $insuranceUpdate = InsuranceUpdate::where('policy_number', $row['no_polis'])
            ->where('insurance_id', $this->insuranceId)
            ->first();
        $isNewInsuranceUpdate = ! $insuranceUpdate;
        $joinDate = $this->parseDate($row['tanggal_mulai'] ?? null, 'tanggal_mulai', $row['no_polis'], $isNewInsuranceUpdate);
        $expiredDate = $this->parseDate($row['tanggal_akhir'] ?? null, 'tanggal_akhir', $row['no_polis'], $isNewInsuranceUpdate);

        if ($isNewInsuranceUpdate) {
            $insuranceUpdate = new InsuranceUpdate();
            $insuranceUpdate->policy_number = $row['no_polis'];
            $insuranceUpdate->insurance_id = $this->insuranceId;
        }

        $stock_inprov_id = InsuranceProvider::where('insurance_provider', preg_replace('/\s+/', '', $row['asuransi_stok'] ?? ''))->value('id');
        $building_inprov_id = InsuranceProvider::where('insurance_provider', preg_replace('/\s+/', '', $row['asuransi_bangunan'] ?? ''))->value('id');

        $insuranceUpdate->stock_inprov_id = $stock_inprov_id;
        $insuranceUpdate->building_inprov_id = $building_inprov_id;
        $insuranceUpdate->stock_worth = $row['nilai_stok'] ?? $insuranceUpdate->stock_worth;
        $insuranceUpdate->actual_stock_worth = (($row['nilai_aktual_stok'] ?? null) !== null && ($row['nilai_aktual_stok'] ?? '') !== '') ? $row['nilai_aktual_stok'] : null;
        $insuranceUpdate->stock_premium = $row['premi_stok'] ?? $insuranceUpdate->stock_premium;
        $insuranceUpdate->building_worth = $row['nilai_bangunan'] ?? $insuranceUpdate->building_worth;
        $insuranceUpdate->building_premium = $row['premi_bangunan'] ?? $insuranceUpdate->building_premium;
        $insuranceUpdate->join_date = $joinDate ?? $insuranceUpdate->join_date;
        $insuranceUpdate->expired_date = $expiredDate ?? $insuranceUpdate->expired_date;
        $insuranceUpdate->user_id = $user_id;
        $insuranceUpdate->notes = $row['catatan'] ?? $insuranceUpdate->notes;
        $insuranceUpdate->status = $row['status'] ?? 'BERJALAN';

        $insuranceUpdate->save();

        return null;
    }

    private function parseDate($value, string $fieldName, string $policyNumber, bool $required = false): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        $dateValue = trim((string) $value);

        if ($dateValue === '') {
            if ($required) {
                throw new \InvalidArgumentException($fieldName.' wajib diisi untuk polis update '.$policyNumber.'.');
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
            throw new \InvalidArgumentException($fieldName.' tidak valid untuk polis update '.$policyNumber.'.');
        }
    }
}
