<?php

namespace App\Http\Controllers\API;

use App\Models\UnitType;
use App\Helpers\ResponseFormatter;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\RequestType;
use Exception;

class UnitTypeController extends Controller{

    public function fetch()
    {
        $unittype = UnitType::orderBy('unit_type')->get();

        return ResponseFormatter::success($unittype,'Data tipe unit berhasil diambil');
    }

    public function add(Request $request)
    {
        try {
            $request->validate([
                'unit_type' => 'required',
            ]);

            $unittype = UnitType::create($request->all());

            return ResponseFormatter::success($unittype,'Data tipe unit berhasil ditambahkan');
        } catch (Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage());
        }
        
    }

    public function edit(Request $request, $id)
    {
        try {
            UnitType::find($id)->update($request->all());
            $edited = UnitType::find($id);

            return ResponseFormatter::success($edited,'Data tipe unit berhasil diedit');
        } catch (Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage());
        }
        
    }

    public function delete(UnitType $unitType)
    {
        try {
            $unitType->delete();

            return ResponseFormatter::success($unitType,'Data tipe unit berhasil dihapus');
        } catch (Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage());
        }
        
    }
}