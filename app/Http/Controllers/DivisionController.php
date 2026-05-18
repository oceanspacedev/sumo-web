<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Divisi;
use Illuminate\Http\Request;
use Exception;

class DivisionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('settings.division.index', [
            'divisions' =>  Divisi::with('area')->orderBy('division')->withTrashed()->get(),
            'areas' => Area::all(),
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
            Divisi::create($request->all());

            return redirect('division')->with('success', 'Data berhasil diinput !');
        } catch (Exception $e) {
            return redirect('division')->with(['error' => $e->getMessage()]);
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
    public function edit(Divisi $division)
    {
        return view('settings.division.edit', [
            'division' => $division,
            'areas' => Area::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Divisi $division)
    {
        try {
            $division->update($request->all());

            return redirect('division')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('division')->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Divisi $division)
    {
        try {
            $division->delete($division);

            return redirect('division')->with('success', 'Data berhasil dinonaktifkan !');
        } catch (Exception $e) {
            return redirect('division')->with(['error' => $e->getMessage()]);
        }
    }

    public function active(Request $request, $id)
    {
        $division = Divisi::onlyTrashed()->find($id);
        $division->deleted_at = null;
        $division->save();
        return redirect('division')->with(['success' => "Berhasil mengatifkan kembali division " . $division->division]);
    }
}
