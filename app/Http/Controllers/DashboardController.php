<?php

namespace App\Http\Controllers;

use App\Models\Insurance;
use App\Models\InsuranceUpdate;
use App\Models\PRCategory;
use App\Models\User;
use App\Models\Product;
use App\Models\ProblemReport;
use App\Models\Rent;
use App\Models\RequestBarang;
use App\Models\RequestSetting;
use App\Models\RequestType;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //INISIALISASI HIGHEST REQUEST ITEM
        $highestRequestItem = RequestBarang::with('request_detail', 'user.division.area')
            ->selectRaw('user_id, request_type_id, date, CASE WHEN areas.id IN (4, 5) AND request_type_id = 2 THEN SUM(request_details.qty_approved) ELSE SUM(request_details.qty_request) END AS highestRequestItem')
            ->join('users', 'requests.user_id', '=', 'users.id')
            ->join('divisions', 'users.division_id', '=', 'divisions.id')
            ->join('areas', 'divisions.area_id', '=', 'areas.id')
            ->join('request_details', 'requests.id', '=', 'request_details.request_id')
            ->where('status_client', '<>', 2)
            ->groupBy('user_id', 'request_type_id', 'date', 'areas.id')
            ->orderBy('highestRequestItem', 'DESC');

        //INISIALISASI HIGHEST REQUEST COST
        $highestRequestCost = RequestBarang::with('request_detail', 'request_detail.product', 'user.division.area')
            ->selectRaw('user_id, request_type_id, date, CASE WHEN areas.id IN (4, 5) AND request_type_id = 2 THEN SUM(request_details.qty_approved * products.price) ELSE SUM(request_details.qty_request * products.price) END AS highestRequestCost')
            ->join('users', 'requests.user_id', '=', 'users.id')
            ->join('divisions', 'users.division_id', '=', 'divisions.id')
            ->join('areas', 'divisions.area_id', '=', 'areas.id')
            ->join('request_details', 'requests.id', '=', 'request_details.request_id')
            ->join('products', 'request_details.product_id', '=', 'products.id')
            ->where('status_client', '<>', 2)
            ->groupBy('user_id', 'request_type_id', 'date', 'areas.id')
            ->orderBy('highestRequestCost', 'DESC');

        //INISIALISASI HIGHEST PROBLEM TOTAL
        $highestProblemTotal = ProblemReport::with('prcategory', 'user')
            ->selectRaw('user_id, COUNT(*) as highestProblemTotal')
            ->groupBy('user_id')
            ->orderBy('highestProblemTotal', 'DESC');

        //INISIALISASI HIGHEST PROBLEM CATEGORY
        $highestProblemCategory = ProblemReport::with('prcategory', 'user')
            ->selectRaw('pr_category_id, COUNT(*) as highestProblemCategory')
            ->groupBy('pr_category_id')
            ->orderBy('highestProblemCategory', 'DESC');

        //INISIALISASI INSURANCE CHART
        $insurances_cost = Insurance::whereNotIn('status', ['TUTUP', 'REFUND', 'PEMBAHARUAN'])
            ->select(
                DB::raw('DATE_FORMAT(expired_date, "%Y-%m") AS month_year'),
                DB::raw('SUM(COALESCE(stock_premium, 0) + COALESCE(building_premium, 0)) AS total_cost')
            )
            ->groupBy('month_year');

        $insurance_updates_cost = InsuranceUpdate::whereNotIn('status', ['TUTUP', 'REFUND', 'PEMBAHARUAN'])
            ->select(
                DB::raw('DATE_FORMAT(expired_date, "%Y-%m") AS month_year'),
                DB::raw('SUM(COALESCE(stock_premium, 0) + COALESCE(building_premium, 0)) AS total_cost')
            )
            ->groupBy('month_year');

        $insurance_cost_union = DB::query()
        ->select('month_year', DB::raw('SUM(total_cost) as total_cost'))
        ->from(function ($query) use ($insurances_cost, $insurance_updates_cost) {
            $query->select('month_year', 'total_cost')
                ->from($insurances_cost)
                ->unionAll($insurance_updates_cost);
        }, 'sub')
        ->groupBy('month_year');
        

        //CHART 1 (HIGHEST REQUEST UNIT)
        if ($request->dateChartRequestItem) {
            $data = explode('-', preg_replace('/\s+/', '', $request->dateChartRequestItem));
            $date1 = Carbon::parse($data[0])->format('Y-m-d');
            $date2 = Carbon::parse($data[1])->format('Y-m-d');
            $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
            $dateChartRequestItem = Carbon::parse($date1)->format('d M Y') . ' - ' . Carbon::parse($data[1])->format('d M Y');
            
            $highestRequestItem = $highestRequestItem->whereBetween('date', [$date1, $date2]);
        } 

        if ($request->request_type_1) {
            $highestRequestItem = $highestRequestItem->where('request_type_id', $request->request_type_1);
        }

        //CHART 2 (HIGHEST REQUEST COST)
        if ($request->dateChartRequestCost) {
            $data = explode('-', preg_replace('/\s+/', '', $request->dateChartRequestCost));
            $date1 = Carbon::parse($data[0])->format('Y-m-d');
            $date2 = Carbon::parse($data[1])->format('Y-m-d');
            $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
            $dateChartRequestCost = Carbon::parse($date1)->format('d M Y') . ' - ' . Carbon::parse($data[1])->format('d M Y');

            $highestRequestCost = $highestRequestCost->whereBetween('date', [$date1, $date2]);
        }
        
        if ($request->request_type_2) {
            $highestRequestCost = $highestRequestCost->where('request_type_id', $request->request_type_2);
        }

        //CHART 3 (TOTAL PROBLEM)
        if ($request->dateChartProblemTotal) {
            $data = explode('-', preg_replace('/\s+/', '', $request->dateChartProblemTotal));
            $date1 = Carbon::parse($data[0])->format('Y-m-d');
            $date2 = Carbon::parse($data[1])->format('Y-m-d');
            $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
            $dateChartProblemTotal = Carbon::parse($date1)->format('d M Y') . ' - ' . Carbon::parse($data[1])->format('d M Y');

            $highestProblemTotal = $highestProblemTotal->whereBetween('date', [$date1, $date2]);
        }

        if ($request->pr_category_id) {
            $highestProblemTotal = $highestProblemTotal->where('pr_category_id', $request->pr_category_id);
        }

        //CHART 4 (HIGHEST PROBLEM CATEGORY)
        if ($request->dateChartProblemCategory) {
            $data = explode('-', preg_replace('/\s+/', '', $request->dateChartProblemCategory));
            $date1 = Carbon::parse($data[0])->format('Y-m-d');
            $date2 = Carbon::parse($data[1])->format('Y-m-d');
            $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
            $dateChartProblemCategory = Carbon::parse($date1)->format('d M Y') . ' - ' . Carbon::parse($data[1])->format('d M Y');

            $highestProblemCategory = $highestProblemCategory->whereBetween('date', [$date1, $date2]);
        }

        //CHART 5 (INSURANCE COST)
        if ($request->dateChartInsuranceCost) {
            $formattedDateChartInsuranceCost = \Carbon\Carbon::createFromFormat('m-Y', $request->dateChartInsuranceCost)->format('Y-m');

            $insurance_cost_union = $insurance_cost_union->where('month_year', $formattedDateChartInsuranceCost);

        }
            // HIGHEST REQUEST ITEM CHART
            $highestRequestItem = $highestRequestItem->limit(10)
            ->get();

            // HIGHEST REQUEST COST CHART
            $highestRequestCost = $highestRequestCost->limit(10)
            ->get();

            // HIGHEST PROBLEM TOTAL CHART
            $highestProblemTotal = $highestProblemTotal->limit(10)
            ->get();

            // HIGHEST PROBLEM CATEGORY CHART
            $highestProblemCategory = $highestProblemCategory->limit(10)
            ->get();

            //INSURANCE COST CHART
            $insurance_cost_union = $insurance_cost_union->orderBy('month_year', 'asc')
            ->get();

            $highestRequestUser = [];
            $highestRequestUnit = [];

            foreach ($highestRequestItem as $hri) {
                $highestRequestUser[] = $hri->user->fullname ?? 'Nonactive User';
                $highestRequestUnit[] = $hri->highestRequestItem ?? 0;
            }

            if ($highestRequestUser == [] || $highestRequestUnit == []) {
                $highestRequestUser = ['null', 'null', 'null'];
                $highestRequestUnit = ['0', '0' ,'0'];
            }

            //HIGHEST REQUEST COST CHART
            $highestRequestCostUser = [];
            $highestRequestCostUnit = [];

            foreach ($highestRequestCost as $hrc) {
                $highestRequestCostUser[] = $hrc->user->fullname ?? 'Nonactive User';
                $highestRequestCostUnit[] = $hrc->highestRequestCost ?? 0;
            }

            if ($highestRequestCostUser == [] || $highestRequestCostUnit == []) {
                $highestRequestCostUser = ['null', 'null', 'null'];
                $highestRequestCostUnit = ['0', '0' ,'0'];
            }

            $request_type_filter = RequestType::where('id', $request->request_type)->first()->request_type ?? '';

            //HIGHEST PROBLEM REPORT COUNT CHART
            $highestProblemTotalUser = [];
            $highestProblemTotalUnit = [];

            foreach ($highestProblemTotal as $hpt) {
                $highestProblemTotalUser[] = $hpt->user->fullname ?? 'Nonactive User';
                $highestProblemTotalUnit[] = $hpt->highestProblemTotal ?? 0;
            }

            if ($highestProblemTotalUser == [] || $highestProblemTotalUnit == []) {
                $highestProblemTotalUser = ['null', 'null', 'null'];
                $highestProblemTotalUnit = ['0', '0' ,'0'];
            }

            $pr_category_filter = PRCategory::where('id', $request->pr_category_id)->first()->problem_report_category ?? '';

            //HIGHEST PROBLEM REPORT CATEGORY CHART
            $highestProblemCategoryUser = [];
            $highestProblemCategoryUnit = [];

            foreach ($highestProblemCategory as $hpc) {
                $highestProblemCategoryUser[] = $hpc->prcategory->problem_report_category ?? 'Nonactive Category';
                $highestProblemCategoryUnit[] = $hpc->highestProblemCategory ?? 0;
            }

            if ($highestProblemCategoryUser == [] || $highestProblemCategoryUnit == []) {
                $highestProblemCategoryUser = ['null', 'null', 'null'];
                $highestProblemCategoryUnit = ['0', '0' ,'0'];
            }

            //COST MONTHLY INSURANCES CHART
            $insurance_cost_total = [];
            $insurance_cost_monthyear = [];

            foreach ($insurance_cost_union as $icu) {
                $insurance_cost_total[] = $icu->total_cost ?? 0;
                $insurance_cost_monthyear[] = $icu->month_year ?? '';
            }

            if ($insurance_cost_total == [] || $insurance_cost_monthyear == []) {
                $insurance_cost_total = ['null', 'null', 'null'];
                $insurance_cost_monthyear = ['0', '0' ,'0'];
            }

            //FOR SUMMARY
            $totalRequest = RequestBarang::count();
            $requestDone = RequestBarang::where('status_client', 1)->count();
            $totalProblem = ProblemReport::count();
            $problemDone = ProblemReport::where('status_client', 1)->count();
            $totalProduct = Product::count();
            $totalUser = User::count();
            $totalInsurance = Insurance::count();
            $totalRent = Rent::count();

            $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type', 'request_approval')
            ->whereHas('request_approval', function ($q) {
                $q->where('approval_type', 'EXECUTOR')
                ->where('approved_by', null);
            })
            ->orderBy('date', 'desc')
            ->limit(5)
            ->take(5)
            ->get();

            $problemReport = ProblemReport::where('closed_by', null)
            ->orderBy('date', 'desc')
            ->limit(5)
            ->take(5)
            ->get();

            $insurances = Insurance::with([
                'stock_insurance_provider',
                'building_insurance_provider',
                'insurance_category',
                'insurance_scope',
                'insurance_update' => function($query) {
                    $query->where('status', '!=', 'TUTUP')
                    ->where('status', '!=', 'REFUND')
                    ->latest('expired_date');
                }])
                ->whereDoesntHave('insurance_update', function($query) {
                    $query->whereIn('status', ['TUTUP', 'REFUND']);
                })
                ->select(['insurances.*', DB::raw('(SELECT MAX(expired_date) FROM insurance_updates WHERE insurance_id = insurances.id) as latest_expired_date')])
                ->orderByRaw("COALESCE((SELECT MAX(expired_date) FROM insurance_updates WHERE insurance_id = insurances.id), insurances.expired_date) ASC")
                ->where('insurances.status', '!=', 'TUTUP')
                ->where('insurances.status', '!=', 'REFUND')
                ->take(5)
                ->get();
                
            $rents = Rent::with([
                'rent_update'  => function($query) {
                    $query->where('status', '!=', 'TUTUP')
                    ->where('status', '!=', 'REFUND')
                    ->latest('expired_date');
                }])
                ->whereDoesntHave('rent_update', function($query) {
                    $query->whereIn('status', ['TUTUP', 'REFUND']);
                })
                ->select(['rents.*', DB::raw('(SELECT MAX(expired_date) FROM rent_updates WHERE rent_id = rents.id) as latest_expired_date')])
                ->orderByRaw("COALESCE((SELECT MAX(expired_date) FROM rent_updates WHERE rent_id = rents.id), rents.expired_date) ASC")
                ->where('rents.status', '!=', 'TUTUP')
                ->where('rents.status', '!=', 'REFUND')
                ->take(5)
                ->get();

            return view('dashboard.index', [
            'totalRequest' => $totalRequest,
            'totalProblem' => $totalProblem,
            'totalProduct' => $totalProduct,
            'totalUser' => $totalUser,
            'requestBarangs' => $requestBarangs,
            'problemReport' => $problemReport,
            'requestSetting' => RequestSetting::all(),
            'totalInsurance' => $totalInsurance,
            'totalRent' => $totalRent,
            'insurances' => $insurances,
            'rents' => $rents,
            'problemDone' => $problemDone,
            'problemPending' => $totalProblem - $problemDone,
            'requestDone' => $requestDone,
            'requestPending' => $totalRequest - $requestDone,
            'highestRequestUser' => $highestRequestUser,
            'highestRequestUnit' => $highestRequestUnit,
            'highestRequestCostUser' => $highestRequestCostUser,
            'highestRequestCostUnit' => $highestRequestCostUnit,
            'request_types' => RequestType::all(),
            'prcategories' => PRCategory::all(),
            'highestProblemTotalUser' => $highestProblemTotalUser,
            'highestProblemTotalUnit' => $highestProblemTotalUnit,
            'highestProblemCategoryUser' => $highestProblemCategoryUser,
            'highestProblemCategoryUnit' => $highestProblemCategoryUnit,
            'request_type_filter' => $request_type_filter,
            'dateChartRequestItem' => $dateChartRequestItem ?? '',
            'dateChartRequestCost' => $dateChartRequestCost ?? '',
            'dateChartProblemTotal' => $dateChartProblemTotal ?? '',
            'pr_category_filter' => $pr_category_filter,
            'dateChartProblemCategory' => $dateChartProblemCategory ?? '',
            'insurance_cost_total' => $insurance_cost_total ?? '',
            'insurance_cost_monthyear' => $insurance_cost_monthyear ?? '',
        ]);
    }

    
    public function indexRequestSettings()
    {
        return view('settings.request-settings.index', [
            'requestSettings' => RequestSetting::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createRequestSettings(Request $request)
    {
        try {
            $request['request_month'] = Carbon::createFromFormat('d-m-Y', '01-' . $request['request_month'])->format('Y-m-d');
            $request['open_date'] = Carbon::createFromFormat('d/m/Y', $request['open_date'])->format('Y-m-d');
            $request['closed_date'] = Carbon::createFromFormat('d/m/Y', $request['closed_date'])->format('Y-m-d');
            RequestSetting::create($request->all());

            return redirect('request-settings')->with('success', 'Pengaturan berhasil diinput !');
        } catch (Exception $e) {
            return redirect('request-settings')->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editRequestSettings(RequestSetting $rs)
    {
        return view('settings.request-settings.edit', [
            'requestSetting' => $rs,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateRequestSettings(Request $request, RequestSetting $rs)
    {
        try {
            $request['request_month'] = Carbon::createFromFormat('d-m-Y', '01-' . $request['request_month'])->format('Y-m-d');
            $request['open_date'] = Carbon::createFromFormat('d/m/Y', $request['open_date'])->format('Y-m-d');
            $request['closed_date'] = Carbon::createFromFormat('d/m/Y', $request['closed_date'])->format('Y-m-d');
            $rs->update($request->all());

            return redirect('request-settings')->with('success', 'Pengaturan berhasil diupdate !');  
        } catch (Exception $e) {
            return redirect('request-settings')->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
