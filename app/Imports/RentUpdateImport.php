<?php

namespace App\Imports;

use App\Models\Rent;
use App\Models\RentUpdate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class RentUpdateImport implements ToModel, WithHeadingRow
{
    public function model(array $row): ?Model
    {
        if (empty($row['kode_sewa_induk'])) {
            return null;
        }

        $rent = Rent::where('rent_code', strtolower($row['kode_sewa_induk']))->first();

        if ($rent) {
            $rentUpdate = RentUpdate::where('rent_code', $row['kode'] ?? '')
                ->where('rent_id', $rent->id)
                ->first();
            $isNewRentUpdate = ! $rentUpdate;
            $rentUpdateLabel = trim((string) ($row['kode'] ?? $row['kode_sewa_induk']));
            $joinDate = $this->parseDate($row['tanggal_mulai'] ?? null, 'tanggal_mulai', $rentUpdateLabel, $isNewRentUpdate);
            $expiredDate = $this->parseDate($row['tanggal_akhir'] ?? null, 'tanggal_akhir', $rentUpdateLabel, $isNewRentUpdate);

            if ($isNewRentUpdate) {
                $prefix = 'RENTUP';
                $count = DB::table('rent_updates')->count() + 1;
                $rent_code = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);

                $rentUpdate = new RentUpdate();
                $rentUpdate->rent_code = $rent_code;
                $rentUpdate->rent_id = $rent->id;
            }

            $rentUpdate->first_party = $row['pihak_pertama'] ?? $rentUpdate->first_party;
            $rentUpdate->second_party = $row['pihak_kedua'] ?? $rentUpdate->second_party;
            $rentUpdate->rent_per_year = $row['sewa_per_tahun'] ?? $rentUpdate->rent_per_year;
            $rentUpdate->cvcs_fund = $row['dana_cvcs'] ?? $rentUpdate->cvcs_fund;
            $rentUpdate->online_fund = $row['dana_online'] ?? $rentUpdate->online_fund;
            $rentUpdate->join_date = $joinDate ?? $rentUpdate->join_date;
            $rentUpdate->expired_date = $expiredDate ?? $rentUpdate->expired_date;
            $rentUpdate->deduction_evidence = $row['bukti_potong'] ?? $rentUpdate->deduction_evidence;
            $rentUpdate->document = $row['berkas'] ?? $rentUpdate->document;
            $rentUpdate->status = $row['status'] ?? 'BERJALAN';
            $rentUpdate->month_before_reminder = $row['reminder_bulan_sebelumnya'] ?? $rentUpdate->month_before_reminder;
            $rentUpdate->notes = $row['catatan'] ?? $rentUpdate->notes;
            $rentUpdate->user_id = Auth::id();

            $rentUpdate->save();

            return null;
        }
        //else belum ada no_polis_induk nya

        return null;
    }

    private function parseDate($value, string $fieldName, string $rentUpdateLabel, bool $required = false): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        $dateValue = trim((string) $value);

        if ($dateValue === '') {
            if ($required) {
                throw new \InvalidArgumentException($fieldName.' wajib diisi untuk update sewa '.$rentUpdateLabel.'.');
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
            throw new \InvalidArgumentException($fieldName.' tidak valid untuk update sewa '.$rentUpdateLabel.'.');
        }
    }
}
