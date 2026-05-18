<?php

namespace App\Http\Controllers;

use App\Models\InsuranceScope;
use Illuminate\Http\Request;
use Exception;

class InScopeController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user() && $request->user()->canAccessInsuranceMenu(), 403);

            return $next($request);
        });

        $this->middleware(function ($request, $next) {
            abort_unless($request->user()->canManageInsurance(), 403);

            return $next($request);
        })->except(['index']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('insurances.inscope.index', [
            'inscopes' => InsuranceScope::orderBy('insurance_scope')->paginate(30),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            InsuranceScope::create($request->all());

            return redirect('inscope')->with('success', 'Data berhasil diinput !');
        } catch (Exception $e) {
            return redirect('inscope')->with(['error' => $e->getMessage()]);
        }
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
    public function edit(InsuranceScope $inscope)
    {
        return view('insurances.inscope.edit', [
            'inscope' => $inscope,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, InsuranceScope $inscope)
    {
        try {
            $inscope->update($request->all());

            return redirect('inscope')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('inscope')->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(InsuranceScope $inscope)
    {
        try {
            $inscope->delete($inscope);

            return redirect('inscope')->with('success', 'Data berhasil dihapus !');
        } catch (Exception $e) {
            return redirect('inscope')->with(['error' => $e->getMessage()]);
        }
    }
}
