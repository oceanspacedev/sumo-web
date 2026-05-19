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

        $username = strtolower(trim($row['username']));
        $user = User::where('username', $username)->first();

        $badanusaha_id = $this->requiredLookupId(BadanUsaha::class, 'badan_usaha', $row['badan_usaha'] ?? null, 'Badan usaha', $username);
        $divisi_id = $this->requiredLookupId(Divisi::class, 'division', $row['divisi'] ?? null, 'Divisi', $username);
        $role_id = $this->requiredLookupId(Role::class, 'role', $row['role'] ?? null, 'Role', $username);
        $approval_id = $this->approvalId($row['approval'] ?? null, $user, $username);

        if (! $approval_id) {
            throw new \InvalidArgumentException('Approval wajib diisi untuk user '.$username.'.');
        }

        if ($user) {
            $user->update([
                'fullname' => strtoupper($row['fullname'] ?? ''),
                'username' => $username,
                'badan_usaha_id' => $badanusaha_id,
                'division_id' => $divisi_id,
                'role_id' => $role_id,
                'approval_id' => $approval_id,
            ]);
        } else {
            return new User([
                'username' => $username,
                'fullname' => strtoupper($row['fullname'] ?? ''),
                'password' => ($row['password'] ?? null) ? bcrypt($row['password']) : bcrypt('complete123'),
                'badan_usaha_id' => $badanusaha_id,
                'division_id' => $divisi_id,
                'role_id' => $role_id,
                'approval_id' => $approval_id,
            ]);
        }
    }

    private function requiredLookupId(string $model, string $column, ?string $value, string $label, string $username): int
    {
        $normalized = preg_replace('/\s+/', '', trim((string) $value));

        if ($normalized === '') {
            throw new \InvalidArgumentException($label.' wajib diisi untuk user '.$username.'.');
        }

        $id = $model::where($column, $normalized)->value('id');

        if (! $id) {
            throw new \InvalidArgumentException($label.' "'.$value.'" tidak ditemukan untuk user '.$username.'.');
        }

        return $id;
    }

    private function approvalId(?string $value, ?User $existingUser, string $username): ?int
    {
        $approvalName = trim((string) $value);

        if ($approvalName === '') {
            return $existingUser ? $existingUser->approval_id : null;
        }

        $id = User::where('fullname', $approvalName)->value('id');

        if (! $id) {
            throw new \InvalidArgumentException('Approval "'.$value.'" tidak ditemukan untuk user '.$username.'.');
        }

        return $id;
    }
}
