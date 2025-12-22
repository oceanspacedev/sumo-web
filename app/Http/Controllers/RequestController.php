<?php

namespace App\Http\Controllers;

use App\Exports\RequestExport;
use App\Exports\RequestMasterQRExport;
use App\Models\Area;
use App\Models\RequestType;
use App\Models\RequestBarang;
use App\Models\RequestDetail;
use App\Models\RequestApproval;
use App\Models\Product;
use App\Models\RequestLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Http\UploadedFile;
use Exception;

class RequestController extends Controller
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
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->whereBetween('date', [$date1, $date2])
                    ->orderBy('date')
                    ->paginate(30);
                } else if ($request->code) {
                    //DIGANTI DULU JADI PENCARIAN BY NAME TADINYA KODE PENGAJUAN
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->whereHas('user', function ($query) use ($request) {
                        $query->where('fullname', 'like', '%'.$request->code.'%');
                    })
                    ->orderBy('date','DESC')
                    ->paginate(30);
                } else if ($request->selectStatusAkhir != null) {
                    if ($request->selectStatusAkhir == 'undone'){
                        $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type', 'request_approval')
                        ->where(function ($query) {
                            $query->where(function ($query) {
                                $query->where('request_type_id', 1)
                                    ->whereHas('request_approval', function ($query) {
                                        $query->where('approval_type', 'ACCOUNTING')
                                            ->whereNotNull('approved_by')
                                            ->where('approval_type', 'EXECUTOR')
                                            ->whereNull('approved_by');
                                    });
                                })
                                ->orWhere(function ($query) {
                                    $query->where('request_type_id', 3)
                                        ->whereHas('request_approval', function ($query) {
                                            $query->where('approval_type', 'MANAGER')
                                                ->whereNotNull('approved_by')
                                                ->where('approval_type', 'EXECUTOR')
                                                ->whereNull('approved_by');
                                        });
                                })
                                ->orWhere(function ($query) {
                                    $query->where('request_type_id', 2)
                                        ->whereHas('request_approval', function ($query) {
                                            $query->where('approval_type', 'EXECUTOR')
                                                ->whereNull('approved_by');
                                        })
                                        ->where('status_client', 0);
                                });
                        })
                        ->orderBy('date', 'DESC')
                        ->paginate(30);
                    } else {
                        $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                        ->whereHas('user', function ($query) use ($request) {
                            $query->where('status_client', $request->selectStatusAkhir);
                        })
                        ->orderBy('date','DESC')
                        ->paginate(30);
                    }
                } else {
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type', 'request_approval')
                    ->orderBy('date', 'desc')
                    ->paginate(30);
                }
                break;
            case 2:
                if ($request->search) {
                    $data = explode('-', preg_replace('/\s+/', '', $request->search));
                    $date1 = Carbon::parse($data[0])->format('Y-m-d');
                    $date2 = Carbon::parse($data[1])->format('Y-m-d');
                    $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->where('request_type_id',1)
                    ->whereBetween('date', [$date1, $date2])
                    ->orderBy('date')
                    ->paginate(30);
                } else if ($request->code) {
                    //DIGANTI DULU JADI PENCARIAN BY NAME TADINYA KODE PENGAJUAN
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->whereHas('user', function ($query) use ($request) {
                        $query->where('fullname', 'like', '%'.$request->code.'%');
                    })
                    ->where('request_type_id',1)
                    ->orderBy('date','DESC')
                    ->paginate(30);
                } else if ($request->selectStatusAkhir != null) {
                    if ($request->selectStatusAkhir == 'undone'){
                        $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type', 'request_approval')
                        ->where(function ($query) {
                            $query->where(function ($query) {
                                $query->where('request_type_id', 1)
                                        ->whereHas('request_approval', function ($query) {
                                            $query->where('approval_type', 'ACCOUNTING')
                                                ->whereNull('approved_by');
                                        });
                                });
                        })
                        ->where('status_client', 0)
                        ->where('request_type_id',1)
                        ->orderBy('date', 'DESC')
                        ->paginate(30);
                    } else {
                        $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                        ->whereHas('user', function ($query) use ($request) {
                            $query->where('status_client', $request->selectStatusAkhir);
                        })
                        ->where('request_type_id',1)
                        ->orderBy('date','DESC')
                        ->paginate(30);
                    }
                } else {
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->where('request_type_id',1)
                    ->orderBy('date', 'desc')
                    ->paginate(30);
                }
                break; 
            case 3:
                if ($request->search) {
                    $data = explode('-', preg_replace('/\s+/', '', $request->search));
                    $date1 = Carbon::parse($data[0])->format('Y-m-d');
                    $date2 = Carbon::parse($data[1])->format('Y-m-d');
                    $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->whereHas('request_type', function($q) use($userDivisi) { $q->where('pic_division_id', $userDivisi); })
                    ->whereBetween('date', [$date1, $date2])
                    ->orderBy('date')
                    ->paginate(30);
                } else if ($request->code) {
                    //DIGANTI DULU JADI PENCARIAN BY NAME TADINYA KODE PENGAJUAN
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->whereHas('user', function ($query) use ($request) {
                        $query->where('fullname', 'like', '%'.$request->code.'%');
                    })
                    ->orderBy('date','DESC')
                    ->paginate(30);
                } else if ($request->selectStatusAkhir != null && $userDivisi != 9 && $userDivisi != 11 && $userDivisi != 12) {
                    if ($request->selectStatusAkhir == 'undone'){
                        $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type', 'request_approval')
                        ->where(function ($query) {
                            $query->where(function ($query) {
                                $query->where('request_type_id', 1)
                                        ->whereHas('request_approval', function ($query) {
                                            $query->where('approval_type', 'ACCOUNTING')
                                                ->whereNotNull('approved_by');
                                        });
                                })
                                ->orWhere(function ($query) {
                                    $query->where('request_type_id', 3)
                                            ->whereHas('request_approval', function ($query) {
                                                $query->where('approval_type', 'MANAGER')
                                                    ->whereNotNull('approved_by');
                                            });
                                })
                                ->orWhere(function ($query) {
                                    $query->where('request_type_id', 2)
                                            ->where('status_client', 0);
                                });
                        })
                        ->whereHas('request_type', function($q) use($userDivisi) { $q->where('pic_division_id', $userDivisi); })
                        ->orderBy('date', 'DESC')
                        ->paginate(30);
                    } else {
                        $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                        ->whereHas('request_type', function($q) use($userDivisi) { $q->where('pic_division_id', $userDivisi); })
                        ->where('status_client', $request->selectStatusAkhir)
                        ->orderBy('date','DESC')
                        ->paginate(30);
                    }
                ##DIVISI WHM
                } else if ($request->selectStatusAkhir != null && $userDivisi == 9) {
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->whereHas('user.division.area', function ($query) {
                        $query->whereIn('area_id', [4,5,14,19]);
                    })
                    ->where('status_client', $request->selectStatusAkhir)
                    ->where('request_type_id', 2)
                    ->orderBy('date','DESC')
                    ->paginate(30);
                ##DIVISI IT
                } else if ($request->selectStatusAkhir != null && $userDivisi == 11) {
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->where('user_id', $user)
                    ->where('status_client', $request->selectStatusAkhir)
                    ->orderBy('date','DESC')
                    ->paginate(30);
                ##DIVISI AUDIT
                } else if ($request->selectStatusAkhir != null && $userDivisi == 12) {
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    // ->whereHas('user.division.area', function ($query) {
                    //     $query->whereIn('area_id', [3,4,5]);
                    // })
                    ->where('status_client', $request->selectStatusAkhir)
                    ->where('request_type_id', 3)
                    ->orderBy('date','DESC')
                    ->paginate(30);
                ##DIVISI WHM
                } else if ($userDivisi == 9) {
                    $requestBarangs = RequestBarang::with('user.division.area','closedby','request_detail','request_type','request_approval')
                    ->whereHas('user.division.area', function ($query) {
                        $query->whereIn('area_id', [4,5,14,19]);
                    })
                    ->where('request_type_id', 2)
                    ->orderBy('date', 'desc')
                    ->paginate(30);
                ##DIVISI MKLI-HO ATAU MKLI-4S
                } else if ($userDivisi == 80) {
                    $requestBarangs = RequestBarang::with('user.division.area','closedby','request_detail','request_type','request_approval')
                    ->whereHas('user.division.area', function ($query) {
                        $query->whereIn('area_id', [6, 11]);
                    })
                    ->where('request_type_id', 2)
                    ->orderBy('date', 'desc')
                    ->paginate(30);
                ##DIVISI IT
                } else if ($userDivisi == 11) {
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type','request_approval')
                    // ->where('user_id', $user)
                    ->orderBy('date', 'desc')
                    ->paginate(30);
                ##DIVISI AUDIT
                } else if ($userDivisi == 12) {
                    $requestBarangs = RequestBarang::with('user.division.area','closedby','request_detail','request_type','request_approval')
                    // ->whereHas('user.division.area', function ($query) {
                    //     $query->whereIn('area_id', [3,4,5]);
                    // })
                    ->where('request_type_id', 3)
                    ->orderBy('date', 'desc')
                    ->paginate(30);
                } else {
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type','request_approval')
                    ->whereHas('request_type', function($q) use($userDivisi) { $q->where('pic_division_id', $userDivisi); })
                    ->orderBy('date', 'desc')
                    ->paginate(30);
                }
                break;
            default:
                if ($request->search){
                    //handle user melakukan pencarian
                    $data = explode('-', preg_replace('/\s+/', '', $request->search));
                    $date1 = Carbon::parse($data[0])->format('Y-m-d');
                    $date2 = Carbon::parse($data[1])->format('Y-m-d');
                    $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->whereBetween('date', [$date1, $date2])
                    ->where('user_id', $user)
                    ->orderBy('date')
                    ->paginate(30);
                } else if ($request->code) {
                    //DIGANTI DULU JADI PENCARIAN BY NAME TADINYA KODE PENGAJUAN
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->whereHas('user', function ($query) use ($request) {
                        $query->where('fullname', 'like', '%'.$request->code.'%');
                    })
                    ->where('user_id', $user)
                    ->orderBy('date','DESC')
                    ->paginate(30);
                } else if ($request->selectStatusAkhir != null) {
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->whereHas('user', function ($query) use ($request) {
                        $query->where('status_client', $request->selectStatusAkhir);
                    })
                    ->where('user_id', $user)                    
                    ->orderBy('date','DESC')
                    ->paginate(30);
                } else {
                    $requestBarangs = RequestBarang::with('user','closedby','request_detail','request_type')
                    ->where('user_id', $user)
                    ->orderBy('date', 'desc')
                    ->paginate(30);
                }            
                break;
        }
        
        return view('request.index', [
            'requestBarangs' => $requestBarangs,
            'request_types' => RequestType::all(),
            'products' => Product::all(),
            'areas' => Area::all(),
        ]);
    }

    public function show($id)
    {
        $requestBarang = RequestBarang::with('request_detail.product')->find($id);

        $grandTotal = 0;

        foreach ($requestBarang->request_detail as $detail) {
            if ($detail->qty_approved === null) {
               $grandTotal += $detail->product->price * $detail->qty_request; 
            } else if ($detail->qty_approved === 0) {
                $grandTotal += $detail->product->price * $detail->qty_approved;
            } else {
                $grandTotal += $detail->product->price * $detail->qty_approved;
            }

        }

        return view('request.show', [
            'requestBarang' => $requestBarang,
            'grandTotal' => $grandTotal,
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
            
            //SEMUA TIPE PENGAJUAN KALAU USER BELUM CLOSE GABISA NAMBAH
            $pengajuanAsset = RequestBarang::with('user')
            ->where('user_id', $user)
            ->where('request_type_id', '1')
            ->whereIn('status_client', [0, 3, 4])
            ->count();
            
            $pengajuanATK = RequestBarang::with('user')
            ->where('user_id', $user)
            ->where('request_type_id', '2')
            ->whereIn('status_client', [0, 3, 4])
            ->count();

            // dd($pengajuanATK);

            $pengajuanNota = RequestBarang::with('user')
            ->where('user_id', $user)
            ->where('request_type_id', '3')
            ->whereIn('status_client', [0, 3, 4])
            ->count();

            if ($pengajuanAsset >= 1 && $pengajuanATK >=1 && $pengajuanNota >=1) {
                return redirect('request')->with(['error' => 'Harap menunggu hingga pengajuan diproses dan status akhir diselesaikan !']);
            }

            //TRY LOGIC
            $requestTypes = [];

            if ($pengajuanAsset == 0) {
                array_push($requestTypes, RequestType::find(1));
            }

            if ($pengajuanNota == 0) {
                array_push($requestTypes, RequestType::find(3));
            } 
            
            if ($pengajuanATK == 0) {
                array_push($requestTypes, RequestType::find(2));
            }
            // END LOGIC
            
            $startDate = '2023-05-03';
            $endDate = '2023-05-31';
            
            //TAMPILKAN OPSI PENGAJUAN ATK BERDASARKAN TANGGAL YANG DITENTUKAN
            // if (now() > Carbon::createFromFormat('Y-m-d', $startDate) && now() < Carbon::createFromFormat('Y-m-d', $endDate)) {
            //     $request_types = [];

            //     if ($pengajuanAsset == 0) {
            //         array_push($request_types, RequestType::find(1));
            //     }
            //     if ($pengajuanNota == 0) {
            //         array_push($request_types, RequestType::find(3));
            //     } 
            //     if ($pengajuanATK == 0) {
            //         array_push($request_types, RequestType::find(2));
            //     }
            // } else {
            //     $request_types = [];
                
            //     if ($pengajuanAsset == 0) {
            //         array_push($request_types, RequestType::find(1));
            //     }
            //     if ($pengajuanNota == 0) {
            //         array_push($request_types, RequestType::find(3));
            //     } 
            // }

            return view('request.addRequest', [
                'request_types' => $requestTypes,
                'products' => Product::all(),
                'pengajuanAsset' => $pengajuanAsset,
            ]);
        } catch (Exception $e) {
            return redirect('request')->with(['error' => $e->getMessage()]);
        }
    }


    public function store(Request $request)
    {
         try {
            $date = Carbon::now()->format('Y-m-d H:i:s');

            if (($request->request_type_id == 1 || $request->request_type_id == 3) && $request->request_file == null) {
                return redirect('request/create')->with('error', 'Harap upload file pengajuan persetujuan Atasan !');
            }

            if ($request->request_type_id == 2 && $request->products == null) {
                return redirect('request/create')->with('error', 'Harap pilih barang yang akan diajukan !');
            }

            if ($request->request_type_id == null) {
                return redirect('request/create')->with('error', 'Harap pilih tipe pengajuan !');
            }

            if ($request->request_type_id == 1 || $request->request_type_id == 3) {
                $request_file = $this->storeImage($request, 'request_file');
                $request_file_2 = $request->request_file_2 == null ? null : $this->storeImage($request, 'request_file_2');

                $requestBarang = RequestBarang::create([
                    'user_id' => $request->user_id,
                    'date' => $date,
                    'total_cost' => 0,
                    'status_po' => 0,
                    'request_type_id' => $request->request_type_id,
                ]);
                $requestBarang->request_file = $request_file;
                $requestBarang->request_file_2 = $request_file_2;
                $requestBarang->request_code = "REQ".$requestBarang->id;
                $requestBarang->save();

                //INSERT APPROVAL
                $approval = [
                    'ACCOUNTING',
                    'MANAGER',
                    'EXECUTOR',
                    'ENDUSER',
                ];

                for ($i = 0; $i < 4; $i++) {
                    $temp = array();
                    $temp['request_id'] = $requestBarang->id;
                    $temp['approval_type'] = $approval[$i];

                    $insertApproval = RequestApproval::create($temp);
                }

                for ($i = 0; $i < count($request->get('qty_requests')); $i++) {
                    $temp = array();
                    $temp['request_id'] = $requestBarang->id;
                    $temp['product_id'] = $request->get('products')[$i];
                    $temp['qty_request'] = $request->get('qty_requests')[$i];
                    $temp['qty_remaining'] = $request->get('qty_remainings')[$i] ?? null;
                    $temp['description'] = $request->get('descriptions')[$i];

                    $insertDetail = RequestDetail::create($temp);
                }

                return redirect('request')->with('success', 'Pengajuan Barang Baru berhasil diinput !');
            }
                ##REQUEST_TYPE_ID SELAIN 1 DAN 3
                $requestBarang = RequestBarang::create([
                    'user_id' => $request->user_id,
                    'date' => $date,
                    'total_cost' => 0,
                    'status_po' => 0,
                    'request_type_id' => $request->request_type_id,
                ]);
                $requestBarang->request_code = "REQ".$requestBarang->id;
                $requestBarang->save();

                //INSERT APPROVAL
                $approval = [
                    'ACCOUNTING',
                    'MANAGER',
                    'EXECUTOR',
                    'ENDUSER',
                ];

                for ($i = 0; $i < 4; $i++) {
                    $temp = array();
                    $temp['request_id'] = $requestBarang->id;
                    $temp['approval_type'] = $approval[$i];

                    $insertApproval = RequestApproval::create($temp);
                }

                for ($i = 0; $i < count($request->get('qty_requests')); $i++) {
                        $temp = array();
                        $temp['request_id'] = $requestBarang->id;
                        $temp['product_id'] = $request->get('products')[$i];
                        $temp['qty_request'] = $request->get('qty_requests')[$i];
                        $temp['qty_remaining'] = $request->get('qty_remainings')[$i] ?? null;
                        $temp['description'] = $request->get('descriptions')[$i];

                        $insertDetail = RequestDetail::create($temp);
                }

                return redirect('request')->with('success', 'Pengajuan berhasil diinput !');            
        } catch (Exception $e) {
            return redirect('request')->with(['error' => $e->getMessage()]);
        }
    }

    public function storeImage(Request $request, $fieldName, $disk = 'public')
    {
        try {
            $this->validate($request, [
                'request_file' => 'required|file|mimes:jpeg,png,jpg,pdf',
                'request_file_2' => 'file|mimes:jpeg,png,jpg,pdf',
            ]);
            
            if ($fieldName == 'request_file') {
                $file = $request->file('request_file');
                $date = Carbon::now()->format('Y-m-d');
                $request_id = $request->request_id;
                $extension = $file->getClientOriginalExtension();
                $path = 'Request_File';
                if (! Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->makeDirectory($path);
                }
                
                $filename = "Req-".$request_id."_".$date."_". time() .".".$extension;
            } else {
                $file = $request->file('request_file_2');

                $date = Carbon::now()->format('Y-m-d');
                $request_id = $request->request_id;
                $extension = $file->getClientOriginalExtension();
                $path = 'Request_File';
                if (! Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->makeDirectory($path);
                }
                
                $filename = "Req2-".$request_id."_".$date."_". time() .".".$extension;
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
            return redirect('request')->with(['error' => $e->getMessage()]);
        }
    }

    public function editStatus(RequestBarang $requestBarang)
    {
        return view('request.editStatus', [
            'requestBarang' => $requestBarang,
        ]);
    }

    public function editStatusClient(RequestBarang $requestBarang)
    {
        return view('request.editStatusClient', [
            'requestBarang' => $requestBarang,
        ]);
    }

    public function editStatusAcc(RequestBarang $requestBarang)
    {
        return view('request.editStatusAcc', [
            'requestBarang' => $requestBarang,
        ]);
    }

    public function updateStatusClient(Request $request, RequestBarang $requestBarang)
    {
        try {
            if ($request->status_client == 0) {
                $requestBarang->user_notes = $request->user_notes;
                $requestBarang->save();
                return redirect('request')->with('success', 'Status masih menunggu !');
            }
            $requestBarang->status_client = $request->status_client;
            $requestBarang->save();

            $getData = RequestApproval::where('request_id', $requestBarang->id)
            ->where('approval_type', 'ENDUSER')
            ->first();
            
            $getData->approved_by = Auth::user()->id;
            $getData->approved_at = Carbon::now()->format('Y-m-d H:i:s');
            $getData->save();

            $requestBarang->user_notes = $request->user_notes;
            $requestBarang->save();

            return redirect('request')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('request')->with(['error' => $e->getMessage()]);
        }
    }

    public function updateStatusAcc(Request $request, RequestBarang $requestBarang)
    {
        try {
            $requestBarang->status_po = $request->status_po;
            $requestBarang->save();
            
            $getData = RequestApproval::where('request_id', $requestBarang->id)
            ->where('approval_type', 'ACCOUNTING')
            ->first();
            
            $getData->approved_by = Auth::user()->id;
            $getData->approved_at = Carbon::now()->format('Y-m-d H:i:s');
            $getData->save();

            return redirect('request')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('request')->with(['error' => $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, RequestBarang $requestBarang)
    {
        try {
            $user = Auth::user()->id;

            $getData = RequestApproval::where('request_id', $requestBarang->id)
            ->where('approval_type', 'EXECUTOR')
            ->first();

            if($request->status == 'PENDING'){
                $getData->approved_by = null;
                $getData->approved_at = null;
                $getData->save();

                $requestBarang->notes = $request->notes;
                $requestBarang->status_client = 0;
                $requestBarang->save();
            }
            
            if($request->status == 'CLOSED'){
                $getData->approved_by = Auth::user()->id;
                $getData->approved_at = Carbon::now()->format('Y-m-d H:i:s');
                $getData->save();

                $requestBarang->notes = $request->notes;
                $requestBarang->status_client = 4;
                $requestBarang->save();
            }
            
            if($request->status == 'CANCELLED'){
                $getData->approved_by = Auth::user()->id;
                $getData->approved_at = Carbon::now()->format('Y-m-d H:i:s');
                $getData->save();
                
                $requestBarang->notes = $request->notes;
                $requestBarang->status_client = 2;
                $requestBarang->save();
            }

            if($request->status == 'PROCESSED'){
                $getData->approved_by = Auth::user()->id;
                $getData->approved_at = Carbon::now()->format('Y-m-d H:i:s');
                $getData->save();
                
                $requestBarang->notes = $request->notes;
                $requestBarang->status_client = 3;
                $requestBarang->save();
            }

            return redirect('request')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('request')->with(['error' => $e->getMessage()]);
        }
    }

    public function showEditPage($id, $requestId)
    {
        $detail = RequestDetail::with('product')->find($id);
        $requestId = RequestBarang::find($requestId);

        return view('request.showEdit', [
            'detail' => $detail,
            'requestId' => $requestId,
        ]);
    }

    public function updateRequest(Request $request, $id)
    {
        try {
            $detail = RequestDetail::find($id);

            $detail->qty_approved = $request->qty_approved;
            $detail->save();

            return redirect('request/'.$request->request_id)->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('request/'.$request->request_id)->with(['error' => $e->getMessage()]);
        }
    }

    public function fixRequest(Request $request, $id)
    {
        try {
            $requestApprov = RequestApproval::where('request_id', $id)
            ->where('approval_type', 'MANAGER')
            ->first();

            $detailRequestNull = RequestDetail::where('request_id', $id)->whereNull('qty_approved')->get();

            if ($detailRequestNull->count() === 0) {
                $requestApprov->approved_by = Auth::user()->id;
                $requestApprov->approved_at = Carbon::now()->format('Y-m-d H:i:s');
                $requestApprov->save();

                return redirect('request')->with('success', 'Pengajuan telah disetujui !');
            }

            foreach ($detailRequestNull as $requestDetail) {
                $requestDetail->qty_approved = $requestDetail->qty_request;
                $requestDetail->save();
            }

            $requestApprov->approved_by = Auth::user()->id;
            $requestApprov->approved_at = Carbon::now()->format('Y-m-d H:i:s');
            $requestApprov->save();

            return redirect('request')->with('success', 'Pengajuan telah disetujui !');
        } catch (Exception $e) {
            return redirect('request')->with(['error' => $e->getMessage()]);
        }
    }

    public function cancelRequest(Request $request, RequestBarang $requestBarang)
    {
        try {
            $requestBarang->status_client = 2;
            $requestBarang->save();

            return redirect('request')->with('success', 'Pengajuan telah dibatalkan !');
        } catch (Exception $e) {
            return redirect('request')->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(RequestBarang $requestBarang)
    {
        try {
            $requestBarang->delete($requestBarang);

            return redirect('request')->with('success', 'Data berhasil dihapus !');
        } catch (Exception $e) {
            return redirect('request')->with(['error' => $e->getMessage()]);
        }
    }

    public function export(Request $request){
        
        if ($request->exportRequest) {
            $data = explode('-', preg_replace('/\s+/', '', $request->exportRequest));
            $date1 = Carbon::parse($data[0])->format('Y-m-d');
            $date2 = Carbon::parse($data[1])->format('Y-m-d');
            $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
            //GET ADDITIONAL ID
            $area_id = $request->area_id;
            $request_type_id = $request->request_type_id;
            $request_type = RequestType::where('id', $request_type_id)->value('request_type');
            $filter_request = $request->selectFilterRequest;
        }

        return Excel::download(new RequestExport($date1, $date2, $area_id, $request_type_id, $filter_request), 'pengajuan_'. str_replace(['/', '\\'], '_', $request_type) . '_'. $date1 . '_to_' . $date2 . '.xlsx');
    }

    public function exportMasterQR(Request $request){
        
        if ($request->exportQR) {
            $data = explode('-', preg_replace('/\s+/', '', $request->exportQR));
            $date1 = Carbon::parse($data[0])->format('Y-m-d');
            // dd($date1);
            $date2 = Carbon::parse($data[1])->format('Y-m-d');
            $date2 = date('Y-m-d', strtotime('+ 1 day', strtotime($date2)));
            //GET ADDITIONAL ID
            // $area_id = $request->area_id;
            $request_type_id = $request->request_type_id;
            $request_type = RequestType::where('id', $request_type_id)->value('request_type');
        }
        
        return Excel::download(new RequestMasterQRExport($date1, $date2, $request_type_id), 'QR_pengajuan_'. str_replace(['/', '\\'], '_', $request_type) . '_'. $date1 . '_to_' . $date2 . '.xlsx');
    }

    public function editApplicant(RequestBarang $requestBarang)
    {
        return view('request.editApplicant', [
            'requestBarang' => $requestBarang,
            'users' => User::whereNotIn('id', [1, 2, 3])->get(),
        ]);
    }

    public function updateApplicant(Request $request, RequestBarang $requestBarang)
    {
        try {
            if ($request->EditType == 'editApplicant') {
                $requestBarang->user_id = $request->user_id;
                $requestBarang->save();

                $newApplicant = User::where('id', $request->user_id)->first()->fullname;
                $oldApplicant = User::where('id', $request->user_id_before)->first()->fullname;

                $requestLog = RequestLog::create([
                    'user_id' => Auth::user()->id,
                    'request_id' => $request->id,
                    'activity' => Auth::user()->fullname . ' merubah pemohon ' . $oldApplicant . ' menjadi ' . $newApplicant,
                ]);
                $requestLog->save();

                return redirect('request')->with('success', 'Berhasil merubah pemohon !');
            } else {
                return redirect('request')->with('error', 'Terjadi kesalahan');
            }

        } catch (Exception $e) {
            return redirect('request')->with(['error' => $e->getMessage()]);
        }
    }

    public function requestLogs()
    {
        return view('request-logs.index', [
            'logs' => RequestLog::paginate(30),
        ]);
    }
}
