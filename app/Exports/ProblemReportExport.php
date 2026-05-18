<?php

namespace App\Exports;

use App\Models\ProblemReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProblemReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected String $date1;
    protected String $date2;

    function __construct(String $date1, String $date2)
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $date1 = $this->date1;
        $date2 = $this->date2;

        return ProblemReport::with('prcategory','user','closedby')
        ->whereBetween('date', [$date1, $date2])
        ->orderBy('date')
        ->get();
    }

    public function headings(): array
    {
        return [
            'pelapor',
            'tgl pelaporan',
            'kategori',
            'detail_pelaporan',
            'status',
            'penjadwalan',
            'diproses oleh',
            'diproses pada',
            'hasil pengerjaan',
            'status akhir'
        ];
    }

    public function map($row): array
    {
        return [
            $row->user->fullname ?? '',
            $row->date,
            $row->prcategory->problem_report_category,
            $row->description,
            $row->status,
            $row->scheduled_at,
            $row->closedby->fullname ?? '',
            $row->closed_at,
            $row->result_desc,
            $row->status_client == 0 ? 'MENUNGGU' : 'SELESAI',
        ];
    }
}
