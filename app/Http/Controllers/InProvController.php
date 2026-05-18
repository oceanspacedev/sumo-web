<?php

namespace App\Http\Controllers;

use App\Models\InsuranceProvider;
use Illuminate\Http\Request;
use Exception;

class InProvController extends Controller
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
        return view('insurances.inprov.index', [
            'inprovs' => InsuranceProvider::paginate(30),
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
            InsuranceProvider::create($request->all());

            return redirect('inprov')->with('success', 'Data berhasil diinput !');
        } catch (Exception $e) {
            return redirect('inprov')->with(['error' => $e->getMessage()]);
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
    public function edit(InsuranceProvider $inprov)
    {
        return view('insurances.inprov.edit', [
            'inprov' => $inprov,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, InsuranceProvider $inprov)
    {
        try {
            $inprov->update($request->all());

            return redirect('inprov')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('inprov')->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(InsuranceProvider $inprov)
    {
        try {
            $inprov->delete($inprov);

            return redirect('inprov')->with('success', 'Data berhasil dihapus !');
        } catch (Exception $e) {
            return redirect('inprov')->with(['error' => $e->getMessage()]);
        }
    }
}
