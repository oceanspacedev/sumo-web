<?php

namespace App\Http\Controllers;

use App\Models\Rent;
use App\Models\RentUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Support\Facades\DB;

class RentUpdateController extends Controller
{
    public function storeUpdate(Request $request)
    {
        try {
            $prefix = 'RENTUP';
            $count = DB::table('rent_updates')->count() + 1;
            $rent_code = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
            $payment_evidence_file = $request->payment_evidence_file == null ? null : $this->storeImage($request, 'payment_evidence_file');

            $request['rent_id'] = $request->rent_id;
            $request['rent_code'] = $rent_code;
            $request['user_id'] = Auth::id();
            $request['join_date'] = Carbon::createFromFormat('d/m/Y', $request['join_date'])->format('Y-m-d');
            $request['expired_date'] = Carbon::createFromFormat('d/m/Y', $request['expired_date'])->format('Y-m-d');
            $rent = RentUpdate::create($request->all());

            $rent->payment_evidence_file = $payment_evidence_file;
            $rent->save();

            return redirect('rent/'.$request->rent_id)->with('success', 'Update Perjanjian Sewa berhasil diinput !');  
        } catch (Exception $e) {
            return redirect('rent/'.$request->rent_id)->with(['error' => $e->getMessage()]);
        }
    }

    public function storeImage(Request $request, $fieldName, $disk = 'public')
    {
        try {
            $this->validate($request, [
                'payment_evidence_file' => 'file|mimes:jpeg,png,jpg,pdf|max:10240',
            ]);
            
            if ($fieldName == 'payment_evidence_file') {
                $file = $request->file('payment_evidence_file');
                $date = Carbon::now()->format('Y-m-d');
                $extension = $file->getClientOriginalExtension();
                $path = 'Rent_File';
                if (! Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->makeDirectory($path);
                }

                $filename = "RENT-PAYMENT-".$date."_". time() .".".$extension;
            } else {
                $file = $request->file('deduction_evidence');
                $date = Carbon::now()->format('Y-m-d');
                $extension = $file->getClientOriginalExtension();
                $path = 'Rent_File';
                if (! Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->makeDirectory($path);
                }

                $filename = "RENT-DEDUCT-".$date."_". time() .".".$extension;
            }

            $file = $this->compressImageFile($file);
    
            $file->storeAs($path, $filename, $disk);
    
            return $filename;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function editUpdate($id, $rentId)
    {
        $rent = RentUpdate::findOrFail($id);
        $rentId = Rent::findOrFail($rentId);

        return view('rents.rent.showEditUpdate', [
            'rent' => $rent,
            'rentId' => $rentId,
        ]);
    }

    public function updateRentUpdate(Request $request, RentUpdate $rentUpdate)
    {
        try {
            $payment_evidence_file = $request->payment_evidence_file == null ? null : $this->storeImage($request, 'payment_evidence_file');
            
            $request['user_id'] = Auth::id();
            $request['join_date'] = Carbon::createFromFormat('d/m/Y', $request['join_date'])->format('Y-m-d');
            $request['expired_date'] = Carbon::createFromFormat('d/m/Y', $request['expired_date'])->format('Y-m-d');
            $rentUpdate->update($request->all());
            
            $rentUpdate->payment_evidence_file = $request->payment_evidence_file == null ? $rentUpdate->payment_evidence_file : $payment_evidence_file;
            $rentUpdate->save();

            return redirect('rent/'.$request->rent_id)->with('success', 'Perjanjian Sewa berhasil diupdate !');  
        } catch (Exception $e) {
            return redirect('rent/'.$request->rent_id)->with(['error' => $e->getMessage()]);
        }
    }

    public function deleteUpdate(RentUpdate $rentUpdate, Rent $rent)
    {
        try {
            $rentUpdate->delete($rentUpdate);

            return redirect('rent/'.$rent->id)->with('success', 'Data berhasil dihapus !');
        } catch (Exception $e) {
            return redirect('rent/'.$rent->id)->with(['error' => $e->getMessage()]);
        }
    }
}
