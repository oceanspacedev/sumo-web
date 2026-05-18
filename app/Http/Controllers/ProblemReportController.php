<?php

namespace App\Http\Controllers;

use App\Exports\ProblemReportExport;
use App\Models\ProblemReport;
use App\Models\PRCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Http\UploadedFile;
use Exception;

class ProblemReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user()->id;
        $userRole = Auth::user()->role_id;
        $userDivisi = Auth::user()->division_id;

        switch ($userRole) {
            case 1:
                if ($request->search) {
                    $data = explode('-', preg_replace('/\s+/', '', $request->search));
                    $date1 = Carbon::parse($data[0])->format('Y-m-d');
                    $date2 = Carbon::parse($data[1])->format('Y-m-d');
                    $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
                    $problems = ProblemReport::with('prcategory','user','closedby')
                    ->whereBetween('date', [$date1, $date2])
                    ->orderBy('date')
                    ->paginate(30);
                } else if ($request->code) {
                    //DIGANTI DULU JADI PENCARIAN BY NAME TADINYA KODE GANGGUAN
                    $problems = ProblemReport::with('prcategory','user','closedby')
                    ->whereHas('user', function ($query) use ($request) {
                        $query->where('fullname', 'like', '%'.$request->code.'%');
                    })
                    ->orderBy('date','DESC')
                    ->paginate(30);
                } else if ($request->selectStatusAkhir != null) {
                    $problems = ProblemReport::with('prcategory','user','closedby')
                    ->where('status_client', $request->selectStatusAkhir)
                    ->orderBy('date', 'desc')
                    ->paginate(30);
                } else {
                    $problems = ProblemReport::with('prcategory','user','closedby')
                    ->orderBy('date', 'desc')
                    ->paginate(30);
                }
                break;
            case 3: 
                //KALAU DVISINYA HCM, TAMPILIN GANGGUAN UMUM AJA
                if ($userDivisi == 6) {
                    if ($request->search) {
                        $data = explode('-', preg_replace('/\s+/', '', $request->search));
                        $date1 = Carbon::parse($data[0])->format('Y-m-d');
                        $date2 = Carbon::parse($data[1])->format('Y-m-d');
                        $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
                        $problems = ProblemReport::with('prcategory','user','closedby')
                        ->where('pr_category_id', 7)
                        ->whereBetween('date', [$date1, $date2])
                        ->orderBy('date')
                        ->paginate(30);
                    } else if ($request->code) {
                        //DIGANTI DULU JADI PENCARIAN BY NAME TADINYA KODE GANGGUAN
                        $problems = ProblemReport::with('prcategory','user','closedby')
                        ->whereHas('user', function ($query) use ($request) {
                            $query->where('fullname', 'like', '%'.$request->code.'%');
                        })
                        ->where('pr_category_id', 7)
                        ->orderBy('date','DESC')
                        ->paginate(30); 
                    } else if ($request->selectStatusAkhir != null) {
                        $problems = ProblemReport::with('prcategory','user','closedby')
                        ->where('status_client', $request->selectStatusAkhir)
                        ->where('pr_category_id', 7)
                        ->orderBy('date', 'desc')
                        ->paginate(30);
                    } else {
                        $problems = ProblemReport::with('prcategory','user','closedby')
                        ->where('pr_category_id', 7)
                        ->orderBy('date', 'desc')
                        ->paginate(30);
                    }
                } else {
                    if ($request->search) {
                        $data = explode('-', preg_replace('/\s+/', '', $request->search));
                        $date1 = Carbon::parse($data[0])->format('Y-m-d');
                        $date2 = Carbon::parse($data[1])->format('Y-m-d');
                        $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
                        $problems = ProblemReport::with('prcategory','user','closedby')
                        ->where('pr_category_id', '!=', 7)
                        ->whereBetween('date', [$date1, $date2])
                        ->orderBy('date')
                        ->paginate(30);
                    } else if ($request->code) {
                        //DIGANTI DULU JADI PENCARIAN BY NAME TADINYA KODE GANGGUAN
                        $problems = ProblemReport::with('prcategory','user','closedby')
                        ->whereHas('user', function ($query) use ($request) {
                            $query->where('fullname', 'like', '%'.$request->code.'%');
                        })
                        ->where('pr_category_id', '!=', 7)
                        ->orderBy('date','DESC')
                        ->paginate(30); 
                    } else if ($request->selectStatusAkhir != null) {
                        $problems = ProblemReport::with('prcategory','user','closedby')
                        ->where('status_client', $request->selectStatusAkhir)
                        ->where('pr_category_id', '!=', 7)
                        ->orderBy('date', 'desc')
                        ->paginate(30);
                    } else {
                        $problems = ProblemReport::with('prcategory','user','closedby')
                        ->where('pr_category_id', '!=', 7)
                        ->orderBy('date', 'desc')
                        ->paginate(30);
                    }
                }
                break;
            default:
                if ($request->search) {
                    $data = explode('-', preg_replace('/\s+/', '', $request->search));
                    $date1 = Carbon::parse($data[0])->format('Y-m-d');
                    $date2 = Carbon::parse($data[1])->format('Y-m-d');
                    $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
                    $problems = ProblemReport::with('prcategory','user','closedby')
                    ->whereBetween('date', [$date1, $date2])
                    ->where('user_id', $user)
                    ->orderBy('date')
                    ->paginate(30);
                } else if ($request->code) {
                    //DIGANTI DULU JADI PENCARIAN BY NAME TADINYA KODE GANGGUAN
                    $problems = ProblemReport::with('prcategory','user','closedby')
                    ->whereHas('user', function ($query) use ($request) {
                        $query->where('fullname', 'like', '%'.$request->code.'%');
                    })
                    ->where('user_id', $user)
                    ->orderBy('date','DESC')
                    ->paginate(30); 
                } else if ($request->selectStatusAkhir != null) {
                    $problems = ProblemReport::with('prcategory','user','closedby')
                    ->where('status_client', $request->selectStatusAkhir)
                    ->orderBy('date', 'desc')
                    ->paginate(30);
                } else {
                    $problems = ProblemReport::with('prcategory','user','closedby')
                    ->where('user_id', $user)
                    ->orderBy('date', 'desc')
                    ->paginate(30);
                }
                break;
        }

        return view('problems.index', [
            'problems' => $problems,
            'prcategories' => PRCategory::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function create()
     {
       try {
            $user = Auth::user()->id;
            
            $clientPending = ProblemReport::with('user')
            ->where('user_id', $user)
            ->where('status_client', '0')
            ->count();

            if ($clientPending >= 1) {
                return redirect('problemReport')->with(['error' => 'Harap menunggu hingga diproses dan status akhir diselesaikan !']);
            }

            return view('problems.addProblem', [
                'prcategories' => PRCategory::all(),
            ]);
        } catch (Exception $e) {
            return redirect('problemReport')->with(['error' => $e->getMessage()]);
        }
     }

    public function store(Request $request)
    {
        try {
            if ($request->pr_category_id == null) {
                return redirect('problemReport/create')->with('error', 'Harap pilih jenis gangguan !');
            }

            if ($request->photo_before == null) {
                return redirect('problemReport/create')->with('error', 'Harap upload foto gangguan/kerusakan !');
            }

            $photo_before = $request->photo_before == null ? null : $this->storeImage($request, 'photo_before');
            $request['date'] = Carbon::now()->format('Y-m-d H:i:s');
            $problem = ProblemReport::create([
                'user_id' => $request->user_id,
                'description' => $request->description,
                'pr_category_id' => $request->pr_category_id,
                'date' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            
            $problem->photo_before = $photo_before;
            $problem->problem_report_code = "PR".$problem->id;
            $problem->save();

            return redirect('problemReport')->with('success', 'Laporan berhasil diinput !');
        } catch (Exception $e) {
            return redirect('problemReport')->with(['error' => $e->getMessage()]);
        }
    }

    public function storeImage(Request $request, $fieldName, $disk = 'public')
    {
        try {
            $this->validate($request, [
                'photo_before' => 'file|image|mimes:jpeg,png,jpg,pdf',
                'photo_after' => 'file|image|mimes:jpeg,png,jpg,pdf',
            ]);
            
            if ($fieldName == 'photo_before') {
                $file = $request->file('photo_before');
                $date = Carbon::now()->format('Y-m-d');
                $extension = $file->getClientOriginalExtension();
                $path = 'Problem_Report_File';
                if (! Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->makeDirectory($path);
                }

                $filename = "PR-BEFORE-".$date."_". time() .".".$extension;
            } else {
                $file = $request->file('photo_after');
                $date = Carbon::now()->format('Y-m-d');
                $extension = $file->getClientOriginalExtension();
                $path = 'Problem_Report_File';
                if (! Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->makeDirectory($path);
                }

                $filename = "PR-AFTER-".$date."_". time() .".".$extension;
            }

            // Use Intervention Image to convert the image
            if (in_array($extension, ['jpeg', 'png', 'jpg']) && $file->getSize() > 2048 * 1024) {
                $compressedImage = Image::make($file)->encode($extension, 30);
                $tmpFile = tempnam(sys_get_temp_dir(), 'compressed-');
                file_put_contents($tmpFile, $compressedImage);
                $file = new UploadedFile($tmpFile, $file->getClientOriginalName(), $file->getClientMimeType(), null, true);
            }
    
            $file->storeAs($path, $filename, $disk);
    
            return $filename;

        } catch (Exception $e) {
            return redirect('problemReport')->with(['error' => $e->getMessage()]);
        }
    }

    public function editStatus(ProblemReport $problem)
    {
        return view('problems.editStatus', [
            'problem' => $problem,
        ]);
    }

    public function editStatusClient(ProblemReport $problem)
    {
        return view('problems.editStatusClient', [
            'problem' => $problem,
        ]);
    }

    public function updateStatusClient(Request $request, ProblemReport $problem)
    {
        try {
            $problem->status_client = $request->status_client;
            $problem->save();

            return redirect('problemReport')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('problemReport')->with(['error' => $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, ProblemReport $problem)
    {
        try {
            $user = Auth::user()->id;
            
            if($request->status == "CLOSED"){
                $problem->closed_by = $user;
                $problem->closed_at = Carbon::now()->format('Y-m-d H:i:s');
            }

            if($request->status == "CANCELLED"){
                $problem->closed_by = $user;
                $problem->closed_at = Carbon::now()->format('Y-m-d H:i:s');
                $problem->status_client = 1;
            }

            if ($request->scheduled_at != null){
                $problem->scheduled_at = Carbon::parse($request->scheduled_at)->format('Y-m-d');
            }

            $photo_after = $request->photo_after == null ? null : $this->storeImage($request, 'photo_after');
            $problem->photo_after = $photo_after;
            $problem->result_desc = $request->result_desc;
            $problem->status = $request->status;       
            $problem->save();

            return redirect('problemReport')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('problemReport')->with(['error' => $e->getMessage()]);
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

    public function export(Request $request){
        if ($request->exportProblemReport) {
            $data = explode('-', preg_replace('/\s+/', '', $request->exportProblemReport));
            $date1 = Carbon::parse($data[0])->format('Y-m-d');
            $date2 = Carbon::parse($data[1])->format('Y-m-d');
            $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
            $problems = ProblemReport::with('prcategory','user','closedby')
                ->whereBetween('date', [$date1, $date2])
                ->orderBy('date')
                ->get();
        }

        return Excel::download(new ProblemReportExport($date1, $date2), 'laporan_gangguan_' . $date1 . '_to_' . $date2 . '.xlsx',);
    }
}
