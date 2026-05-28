<?php

namespace App\Exports;

use App\Models\Divisi;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RequestExport implements FromArray, WithHeadings, WithMapping
{
    protected String $date1;
    protected String $date2;
    protected String $area_id;
    protected String $request_type_id;
    protected String $filter_request;
    protected $products;
    protected $divisions;
    protected $totalsByProductAndDivision;

    function __construct(String $date1, String $date2, String $area_id, String $request_type_id, String $filter_request)
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
        $this->area_id = $area_id;
        $this->request_type_id = $request_type_id;
        $this->filter_request = $filter_request;
    }

    public function array(): array
    {
        $products = $this->getProducts();
        $divisions = $this->getDivisions();
        $totalsByProductAndDivision = $this->getTotalsByProductAndDivision();
        $result = [];
        $divisionTotalItems = $divisions->mapWithKeys(function ($division) {
            return [$division->id => 0];
        })->toArray();
        $divisionPriceItems = $divisionTotalItems;

        foreach ($products as $product) {
            $qty = [];
            $totalPricePerDivision = [];

            foreach ($divisions as $division) {
                $total = $totalsByProductAndDivision[$product->id.':'.$division->id] ?? 0;
                $totalPricePerDivision[$division->division] = ($total * $product->price);
                $qty[$division->id] = $total;
                $divisionTotalItems[$division->id] += $total;
                $divisionPriceItems[$division->id] += $total * $product->price;
            }

            $totalPricePerProduct = array_sum($totalPricePerDivision);
            $totalProductPerProduct = array_sum($qty);

            array_push($result, [
                'product_name' => $product->product,
                'unit_type' => $product->unit_type->unit_type ?? '',
                'price' => $product->price,
                'qty' => $qty,
                'total_item' => $totalProductPerProduct,
                'total_price' => $totalPricePerProduct,
            ]);
        }

        $divisionTotalItems = array_values($divisionTotalItems);
        $divisionPriceItems = array_values($divisionPriceItems);

        $divisionTotalRow = [
            'product_name' => 'Total Item per Divisi',
            'unit_type' => '',
            'price' => '',
            'qty' => $divisionTotalItems,
            'total_item' => array_sum($divisionTotalItems),
            'total_price' => '',
        ];

        $divisionPriceRow = [
            'product_name' => 'Total Biaya per Divisi',
            'unit_type' => '',
            'price' => '',
            'qty' => $divisionPriceItems,
            'total_item' => '',
            'total_price' => array_sum($divisionPriceItems),
        ];

        array_push($result, $divisionTotalRow, $divisionPriceRow);

        return $result;
    }

    public function headings(): array
    {
        $division = $this->getDivisions()->pluck('division')->toArray();

        $headings = ['Barang', 'Tipe Unit', 'Harga'];
        $endHeadings = ['Total Item', 'Total Biaya'];
        $headings = array_merge($headings, $division, $endHeadings);

        return $headings;
    }

    public function map($row): array
    {
        $result = [$row['product_name'], $row['unit_type'], $row['price']];
        $result = array_merge($result, $row['qty'], [$row['total_item'], $row['total_price']]);
        return $result;
    }

    protected function getProducts()
    {
        if ($this->products === null) {
            $this->products = Product::with('unit_type')
                ->where('category_id', $this->request_type_id)
                ->get();
        }

        return $this->products;
    }

    protected function getDivisions()
    {
        if ($this->divisions === null) {
            $this->divisions = Divisi::orderBy('division')
                ->where('area_id', $this->area_id)
                ->whereNull('deleted_at') // Filter divisi yang tidak dihapus
                ->get();
        }

        return $this->divisions;
    }

    protected function getTotalsByProductAndDivision()
    {
        if ($this->totalsByProductAndDivision !== null) {
            return $this->totalsByProductAndDivision;
        }

        $productIds = $this->getProducts()->pluck('id')->toArray();
        $divisionIds = $this->getDivisions()->pluck('id')->toArray();

        if (empty($productIds) || empty($divisionIds)) {
            $this->totalsByProductAndDivision = collect();

            return $this->totalsByProductAndDivision;
        }

        $query = DB::table('request_details')
            ->join('requests', 'request_details.request_id', '=', 'requests.id')
            ->join('users', 'requests.user_id', '=', 'users.id')
            ->join('divisions', 'users.division_id', '=', 'divisions.id')
            ->join('areas', 'divisions.area_id', '=', 'areas.id')
            ->where('requests.request_type_id', $this->request_type_id)
            ->whereIn('request_details.product_id', $productIds)
            ->whereIn('divisions.id', $divisionIds)
            ->where('divisions.area_id', $this->area_id)
            ->whereNull('request_details.deleted_at')
            ->whereNull('requests.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereNull('divisions.deleted_at')
            ->whereNull('areas.deleted_at')
            ->whereBetween('requests.created_at', [$this->date1, $this->date2]);

        switch ($this->filter_request) {
            case 0:
                $query->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('request_approvals')
                        ->whereColumn('request_approvals.request_id', 'requests.id')
                        ->where('request_approvals.approval_type', 'EXECUTOR')
                        ->whereNull('request_approvals.approved_by')
                        ->whereNull('request_approvals.deleted_at');
                });
                break;
            case 1:
                $query->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('request_approvals')
                        ->whereColumn('request_approvals.request_id', 'requests.id')
                        ->where('request_approvals.approval_type', 'EXECUTOR')
                        ->whereNotNull('request_approvals.approved_by')
                        ->whereNull('request_approvals.deleted_at');
                });
                break;
            case 3:
                $query->where('requests.status_client', '!=', 2);
                break;
            case 4:
                $query->where('requests.status_client', 2);
                break;
            case 5:
                $query->where('requests.status_client', 4);
                break;
            default:
                $query->where('requests.status_client', '!=', 2);
                break;
        }

        $this->totalsByProductAndDivision = $query
            ->select(
                'request_details.product_id',
                'divisions.id as division_id',
                DB::raw('SUM(COALESCE(request_details.qty_approved, request_details.qty_request)) as total_qty')
            )
            ->groupBy('request_details.product_id', 'divisions.id')
            ->get()
            ->mapWithKeys(function ($row) {
                return [$row->product_id.':'.$row->division_id => $row->total_qty + 0];
            });

        return $this->totalsByProductAndDivision;
    }
}
