<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;

class UserTemplateExport implements WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function headings(): array
    {
        return [
            'username',
            'fullname',
            'password',
            'badan_usaha',
            'divisi',
            'role',
            'approval',
        ];
    }
}
