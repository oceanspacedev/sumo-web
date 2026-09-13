<?php

namespace App\Imports;

use App\Models\Rent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class RentImport implements ToModel, WithHeadingRow
{
    public function model(array $row): ?Model
    {
        if (empty($row['kode']) && empty($row['nama_bangunan']) && empty($row['alamat_bangunan'])) {
            return null;
        }

        $rent = Rent::where('rent_code', strtolower($row['kode'] ?? ''))->first();
        $rentLabel = trim((string) ($row['kode'] ?? $row['nama_bangunan'] ?? $row['alamat_bangunan'] ?? 'baru'));
        $joinDate = $this->parseDate($row['tanggal_mulai'] ?? null, 'tanggal_mulai', $rentLabel, ! $rent);
        $expiredDate = $this->parseDate($row['tanggal_akhir'] ?? null, 'tanggal_akhir', $rentLabel, ! $rent);

        if ($rent) {
            $rent->update([
                'rented_address' => $row['alamat_bangunan'] ?? $rent->rented_address,
                'rented_detail' => $row['nama_bangunan'] ?? $rent->rented_detail,
                'first_party' => $row['pihak_pertama'] ?? $rent->first_party,
                'second_party' => $row['pihak_kedua'] ?? $rent->second_party,
                'rent_per_year' => $row['sewa_per_tahun'] ?? $rent->rent_per_year,
                'cvcs_fund' => $row['dana_cvcs'] ?? $rent->cvcs_fund,
                'online_fund' => $row['dana_online'] ?? $rent->online_fund,
                'join_date' => $joinDate ?? $rent->join_date,
                'expired_date' => $expiredDate ?? $rent->expired_date,
                'deduction_evidence' => $row['bukti_potong'] ?? $rent->deduction_evidence,
                'document' => $row['berkas'] ?? $rent->document,
                'status' => $row['status'] ?? 'BERJALAN',
                'month_before_reminder' => $row['reminder_bulan_sebelumnya'] ?? $rent->month_before_reminder,
                'notes' => $row['catatan'] ?? $rent->notes,
                'user_id' => Auth::id(),
            ]);

            return null;
        } else {
            $prefix = 'RENT';
            $count = DB::table('rents')->count() + 1;
            $rent_code = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);

            return new Rent([
                'rent_code' => $rent_code,
                'rented_address' => $row['alamat_bangunan'] ?? null,
                'rented_detail' => $row['nama_bangunan'] ?? null,
                'first_party' => $row['pihak_pertama'] ?? null,
                'second_party' => $row['pihak_kedua'] ?? null,
                'rent_per_year' => $row['sewa_per_tahun'] ?? null,
                'cvcs_fund' => $row['dana_cvcs'] ?? null,
                'online_fund' => $row['dana_online'] ?? null,
                'join_date' => $joinDate,
                'expired_date' => $expiredDate,
                'deduction_evidence' => $row['bukti_potong'] ?? null,
                'document' => $row['berkas'] ?? null,
                'status' => $row['status'] ?? 'BERJALAN',
                'month_before_reminder' => $row['reminder_bulan_sebelumnya'] ?? null,
                'notes' => $row['catatan'] ?? null,
                'user_id' => Auth::id(),
            ]);
        }
    }

    private function parseDate($value, string $fieldName, string $rentLabel, bool $required = false): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        $dateValue = trim((string) $value);

        if ($dateValue === '') {
            if ($required) {
                throw new \InvalidArgumentException($fieldName.' wajib diisi untuk sewa '.$rentLabel.'.');
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
            throw new \InvalidArgumentException($fieldName.' tidak valid untuk sewa '.$rentLabel.'.');
        }
    }
}
