<?php

namespace App\Imports;

use App\Models\Rent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class RentImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $rent = new Rent();
        $rent = $rent->where('rent_code', strtolower($row['kode']));
        if ($rent->first()) {
            $rent->update([
                'rented_address' => $row['alamat_bangunan'],
                'rented_detail' => $row['nama_bangunan'],
                'first_party' => $row['pihak_pertama'],
                'second_party' => $row['pihak_kedua'],
                'rent_per_year' => $row['sewa_per_tahun'],
                'cvcs_fund' => $row['dana_cvcs'],
                'online_fund' => $row['dana_online'],
                'join_date' => Carbon::createFromFormat('Y-m-d', $row['tanggal_mulai']),
                'expired_date' => Carbon::createFromFormat('Y-m-d', $row['tanggal_akhir']),
                'deduction_evidence' => $row['bukti_potong'],
                'document' => $row['berkas'],
                'status' => $row['status'] ?? 'BERJALAN',
                'month_before_reminder' => $row['reminder_bulan_sebelumnya'],
                'notes' => $row['catatan'],
                'user_id' => Auth::id(),
            ]);
        } else {
            $prefix = 'RENT';
            $count = DB::table('rents')->count() + 1;
            $rent_code = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);

            return new Rent([
                'rent_code' => $rent_code,
                'rented_address' => $row['alamat_bangunan'],
                'rented_detail' => $row['nama_bangunan'],
                'first_party' => $row['pihak_pertama'],
                'second_party' => $row['pihak_kedua'],
                'rent_per_year' => $row['sewa_per_tahun'],
                'cvcs_fund' => $row['dana_cvcs'],
                'online_fund' => $row['dana_online'],
                'join_date' => Carbon::createFromFormat('Y-m-d', $row['tanggal_mulai']),
                'expired_date' => Carbon::createFromFormat('Y-m-d', $row['tanggal_akhir']),
                'deduction_evidence' => $row['bukti_potong'],
                'document' => $row['berkas'],
                'status' => $row['status'] ?? 'BERJALAN',
                'month_before_reminder' => $row['reminder_bulan_sebelumnya'],
                'notes' => $row['catatan'],
                'user_id' => Auth::id(),
            ]);
        }
    }
}
