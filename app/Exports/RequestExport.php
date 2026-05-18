<?php

namespace App\Exports;

use App\Models\RequestDetail;
use App\Models\RequestBarang;
use App\Models\Divisi;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\DB;

class RequestExport implements FromArray, WithHeadings, WithMapping
{
    protected String $date1;
    protected String $date2;
    protected String $area_id;
    protected String $request_type_id;
    protected String $filter_request;

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
        $area_id = $this->area_id;
        $request_type_id = $this->request_type_id;
        $products = Product::where('category_id', $request_type_id)->get();
        $divisions = Divisi::orderBy('division')
            ->where('area_id', $area_id)
            ->whereNull('deleted_at') // Filter divisi yang tidak dihapus
            ->get();
        $result = [];
        $totalPrice = 0;
        $totalItemsAllProducts = 0;
        $filter_request = $this->filter_request;

        foreach ($products as $product) {
            $qty = [];
            $totalPricePerProduct = [];
            $totalPricePerDivision = [];
            $totalProductPerProduct = [];
            $totalItemsPerDivision = [];

            foreach ($divisions as $division) {
                $total = 0;

                $requestsQuery = RequestBarang::with(['user' => function($query) {
                        $query->whereNull('deleted_at');
                    }, 'user.division' => function($query) {
                        $query->whereNull('deleted_at');
                    }, 'user.division.area', 'closedby', 'request_detail.product', 'request_type', 'request_approval'])
                    ->where('request_type_id', $request_type_id)
                    ->whereHas('user', function ($query) use ($division, $area_id) {
                        $query->where('division_id', $division->id)
                              ->whereNull('deleted_at')
                              ->whereHas('division.area', function ($query) use ($area_id) {
                                  $query->where('area_id', $area_id);
                              });
                    })
                    ->whereBetween('created_at', [$this->date1, $this->date2]);

                switch ($filter_request) {
                    case 0:
                        $requestsQuery->whereHas('request_approval', function ($q) {
                            $q->where('approval_type', 'EXECUTOR')
                              ->where('approved_by', null);
                        });
                        break;
                    case 1:
                        $requestsQuery->whereHas('request_approval', function ($q) {
                            $q->where('approval_type', 'EXECUTOR')
                              ->where('approved_by', '!=', null);
                        });
                        break;
                    case 3:
                        $requestsQuery->where('status_client', '!=', 2);
                        break;
                    case 4:
                        $requestsQuery->where('status_client', 2);
                        break;
                    case 5:
                        $requestsQuery->where('status_client', 4);
                        break;
                    default:
                        $requestsQuery->where('status_client', '!=', 2);
                        break;
                }

                $requests = $requestsQuery->get();

                foreach ($requests as $request) {
                    foreach ($request->request_detail as $reqdetail) {
                        if ($reqdetail->product_id == $product->id) {
                            $total += $reqdetail->qty_approved ?? $reqdetail->qty_request;
                        }
                    }
                }
                $totalPricePerDivision[$division->division] = ($total * $product->price);
                $qty[$division->id] = $total;
                $totalItemsPerDivision[$division->division] = $total;
            }
            $totalPricePerProduct = array_sum($totalPricePerDivision);
            $totalPrice += $totalPricePerProduct;
            $totalProductPerProduct = array_sum($qty);
            $totalItemsAllDivisions = array_sum($totalItemsPerDivision);

            array_push($result, [
                'product_name' => $product->product,
                'unit_type' => $product->unit_type->unit_type,
                'price' => $product->price,
                'qty' => $qty,
                'total_item' => $totalProductPerProduct,
                'total_price' => $totalPricePerProduct,
            ]);
        }

        $divisionTotalItems = [];
        $divisionPriceItems = [];

        foreach ($divisions as $division) {
            $divisionTotal = 0;
            $divisionPrice = 0;

            foreach ($result as $product) {
                if (isset($product['qty'][$division->id])) {
                    $divisionTotal += $product['qty'][$division->id];
                    $divisionPrice += $product['qty'][$division->id] * $product['price'];
                }
            }
            $divisionTotalItems[] = $divisionTotal;
            $divisionPriceItems[] = $divisionPrice;
        }

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
        $area_id = $this->area_id;

        $division = DB::table('divisions')
            ->where('area_id', $area_id)
            ->whereNull('deleted_at') // Filter divisi yang tidak dihapus
            ->orderBy('division')
            ->pluck('division')
            ->toArray();

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
}
