<?php

namespace App\Http\Controllers;

use App\Models\Insurance;
use App\Models\InsuranceUpdate;
use App\Models\InsuranceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;

class InsuranceUpdateController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user() && $request->user()->canManageInsurance(), 403);

            return $next($request);
        });
    }

    public function editUpdate($id, $insuranceId)
    {
        $insurance = InsuranceUpdate::findOrFail($id);
        $insuranceId = Insurance::findOrFail($insuranceId);


        return view('insurances.insurance.showEditUpdate', [
            'insurance' => $insurance,
            'inprovs' => InsuranceProvider::orderBy('insurance_provider')->get(),
            'insuranceId' => $insuranceId,

        ]);
    }

    public function updateInsuranceUpdate(Request $request, InsuranceUpdate $insuranceUpdate)
    {
        try {
            $user = Auth::user()->id;

            $request['user_id'] = $user;
            $request['join_date'] = Carbon::createFromFormat('d/m/Y', $request['join_date'])->format('Y-m-d');
            $request['expired_date'] = Carbon::createFromFormat('d/m/Y', $request['expired_date'])->format('Y-m-d');
            $insuranceUpdate->update($request->all());

            return redirect('insurance/'.$request->insurance_id)->with('success', 'Asuransi berhasil diupdate !');  
        } catch (Exception $e) {
            return redirect('insurance/'.$request->insurance_id)->with(['error' => $e->getMessage()]);
        }
    }

    public function deleteUpdate(InsuranceUpdate $insuranceUpdate, Insurance $insurance)
    {
        try {
            $insuranceUpdate->delete($insuranceUpdate);

            return redirect('insurance/'.$insurance->id)->with('success', 'Data berhasil dihapus !');
        } catch (Exception $e) {
            return redirect('insurance/'.$insurance->id)->with(['error' => $e->getMessage()]);
        }
    }
}
