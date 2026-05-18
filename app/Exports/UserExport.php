<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UserExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return User::with(['role','division','badan_usaha'])->orderBy('fullname')->get();
    }

    public function headings(): array
    {
        return [
            'username',
            'fullname',
            'badan_usaha',
            'divisi',
            'role',
            'approval',
        ];
    }

    public function map($user) : array
    {
        return [
            $user->username,
            $user->fullname,
            $user->badan_usaha->badan_usaha,
            $user->division->division,
            $user->role->role,
            $user->approval->fullname,
        ];
    }
}
