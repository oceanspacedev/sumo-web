<?php

namespace App\Exports;

use App\Models\RequestBarang;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RequestMasterQRExport implements FromCollection, WithHeadings, WithMapping
{
    protected String $date1;
    protected String $date2;
    // protected String $area_id;
    protected String $request_type_id;
    
    function __construct(String $date1, String $date2, String $request_type_id)
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
        $this->request_type_id = $request_type_id;
    }

    public function collection()
    {
        // $area_id = $this->area_id;
        $request_type_id = $this->request_type_id;

        return RequestBarang::with('user.division', 'user.division.area','closedby','request_detail', 'request_detail.product','request_type', 'request_approval')
        ->where('request_type_id', $request_type_id)
        // ->whereHas('user.division.area', function ($query) use ($area_id) {
        //     $query->whereIn('area_id', [$area_id]);
        // })
        ->whereBetween('created_at',[$this->date1,$this->date2])
        ->get();
                
    }

    public function headings(): array
    {
        return [
            'id',
            'nama_barang',
            'pemohon',
        ];
    }

    public function map($reqbar) : array
    {
        $data = [];

        foreach ($reqbar->request_detail as $detail) {
            $data[] = [
                $detail->id,
                $detail->product->product,
                $reqbar->user->fullname,
            ];
        }

        return $data;
    }
}
