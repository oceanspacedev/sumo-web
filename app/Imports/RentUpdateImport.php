<?php

namespace App\Imports;

use App\Models\Rent;
use App\Models\RentUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class RentUpdateImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $rent = Rent::where('rent_code', strtolower($row['kode_sewa_induk']))->first();

        if ($rent) {
            $rentUpdate = RentUpdate::where('rent_code', $row['kode'])
                ->where('rent_id', $rent->id)
                ->first();

            if (!$rentUpdate) {
                $prefix = 'RENTUP';
                $count = DB::table('rent_updates')->count() + 1;
                $rent_code = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);

                $rentUpdate = new RentUpdate();
                $rentUpdate->rent_code = $rent_code;
                $rentUpdate->rent_id = $rent->id;
            }

            $rentUpdate->first_party = $row['pihak_pertama'];
            $rentUpdate->second_party = $row['pihak_kedua'];
            $rentUpdate->rent_per_year = $row['sewa_per_tahun'];
            $rentUpdate->cvcs_fund = $row['dana_cvcs'];
            $rentUpdate->online_fund = $row['dana_online'];
            $rentUpdate->join_date = Carbon::createFromFormat('Y-m-d', $row['tanggal_mulai']);
            $rentUpdate->expired_date = Carbon::createFromFormat('Y-m-d', $row['tanggal_akhir']);
            $rentUpdate->deduction_evidence = $row['bukti_potong'];
            $rentUpdate->document = $row['berkas'];
            $rentUpdate->status = $row['status'] ?? 'BERJALAN';
            $rentUpdate->month_before_reminder = $row['reminder_bulan_sebelumnya'];
            $rentUpdate->notes = $row['catatan'];
            $rentUpdate->user_id = Auth::id();

            $rentUpdate->save();
        }
        //else belum ada no_polis_induk nya
    }
}
