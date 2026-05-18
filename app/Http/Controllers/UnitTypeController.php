<?php

namespace App\Http\Controllers;

use App\Models\UnitType;
use Illuminate\Http\Request;

class UnitTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('master.unit_type.index', [
            'unit_types' => UnitType::orderBy('unit_type')->get(),
        ]);
    }

    public function get()
    {
        $unit_type = UnitType::orderBy('unit_type')->get();

        return response()->json($unit_type);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        try {
            UnitType::create($request->all());

            return redirect('unittype')->with('success', 'Data berhasil diinput !');
        } catch (Exception $e) {
            return redirect('unittype')->with(['error' => $e->getMessage()]);
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
    public function edit(UnitType $unit_type)
    {
        return view('master.unit_type.edit', [
            'unit_type' => $unit_type
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UnitType $unit_type)
    {
        try {
            $unit_type->update($request->all());

            return redirect('unittype')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('unittype')->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(UnitType $unit_type)
    {
        try {
            $unit_type->delete($unit_type);

            return redirect('unittype')->with('success', 'Data berhasil dihapus !');
        } catch (Exception $e) {
            return redirect('unittype')->with(['error' => $e->getMessage()]);
        }
    }
}
