<?php

namespace App\Http\Controllers;

use App\Models\Divisi;
use App\Models\User;
use App\Models\RequestType;
use Illuminate\Http\Request;

class RequestTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('master.request_type.index', [
            'request_types' => RequestType::with('division')->get(),
            'divisions' => Divisi::all(),
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
            RequestType::create($request->all());

            return redirect('requesttype')->with('success', 'Data berhasil diinput !');
        } catch (Exception $e) {
            return redirect('requesttype')->with(['error' => $e->getMessage()]);
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
    public function edit(RequestType $request_type)
    {
        return view('master.request_type.edit', [
            'request_type' => $request_type,
            'divisions' => Divisi::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RequestType $request_type)
    {
        try {
            $request_type->update($request->all());

            return redirect('requesttype')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('requesttype')->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(RequestType $request_type)
    {
        try {
            $request_type->delete($request_type);

            return redirect('requesttype')->with('success', 'Data berhasil dihapus !');
        } catch (Exception $e) {
            return redirect('requesttype')->with(['error' => $e->getMessage()]);
        }
    }
}
