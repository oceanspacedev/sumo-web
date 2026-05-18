<?php

namespace App\Imports;

use App\Models\BadanUsaha;
use App\Models\Divisi;
use App\Models\Role;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        if (empty($row['username'])) {
            // stop processing if username is empty
            return null;
        }
        
        $user = new User();
        $user = $user->where('username', strtolower($row['username']));
        if ($user->first()) {
            error_log($row['fullname'] . $row['role']);
            $badanusaha_id = BadanUsaha::where('badan_usaha', preg_replace('/\s+/', '', $row['badan_usaha']))->first()->id;
            $divisi_id = Divisi::where('division', preg_replace('/\s+/', '', $row['divisi']))->first()->id;
            $user->update([
                'fullname' => strtoupper($row['fullname']),
                'username' => strtolower($row['username']),
                'badan_usaha_id' => $badanusaha_id,
                'division_id' => $divisi_id,
                'role_id' => Role::where('role', preg_replace('/\s+/', '', $row['role']))->first()->id,
                'approval_id' => User::where('fullname', $row['approval'])->first()->id,
            ]);
        } else {
            $divisi_id = Divisi::where('division', preg_replace('/\s+/', '', $row['divisi']))->first()->id;
            error_log($row['username'] . $row['divisi']);
            return new User([
                'username' => strtolower($row['username']),
                'fullname' => strtoupper($row['fullname']),
                'password' => $row['password'] ? bcrypt($row['password']) : bcrypt('complete123'),
                'badan_usaha_id' => BadanUsaha::where('badan_usaha', preg_replace('/\s+/', '', $row['badan_usaha']))->first()->id,
                'division_id' => $divisi_id,
                'role_id' => Role::where('role', preg_replace('/\s+/', '', $row['role']))->first()->id,
                'approval_id' => User::where('fullname', $row['approval'])->first()->id,
            ]);
        }
    }
}
