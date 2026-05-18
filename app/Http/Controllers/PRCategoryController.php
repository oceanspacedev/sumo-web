<?php

namespace App\Http\Controllers;

use App\Models\PRCategory;
use Illuminate\Http\Request;

class PRCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('settings.prcategory.index', [
            'prcategories' => PRCategory::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        try {
            PRCategory::create($request->all());

            return redirect('prcategory')->with('success', 'Data berhasil diinput !');
        } catch (Exception $e) {
            return redirect('prcategory')->with(['error' => $e->getMessage()]);
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
    public function edit(PRCategory $prcategory)
    {
        return view('settings.prcategory.edit', [
            'prcategory' => $prcategory,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PRCategory $prcategory)
    {
        try {
            $prcategory->update($request->all());

            return redirect('prcategory')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('prcategory')->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(PRCategory $prcategory)
    {
        try {
            $prcategory->delete($prcategory);

            return redirect('prcategory')->with('success', 'Data berhasil dihapus !');
        } catch (Exception $e) {
            return redirect('prcategory')->with(['error' => $e->getMessage()]);
        }
    }
}
