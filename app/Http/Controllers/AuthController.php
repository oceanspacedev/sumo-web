<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RequestBarang;
use App\Models\RequestDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;
use Milon\Barcode\DNS2D;

class AuthController extends Controller
{
    public function login()
    {
        return view('auths.login');
    }

    public function postlogin(Request $request)
    {
        if (Auth::attempt($request->only('username','password'))) {
            return redirect('/dashboard');
        }

        return redirect('/login')->withErrors([
            'login_error' => 'Username atau password salah. Silakan coba lagi.',
        ]);
    }

    public function logout()
    {
        Auth::logout();

        return redirect('/login');
    }

    public function scanqr()
    {
        return view('auths.scanqr');
    }

    public function search(Request $request)
    {
        $id = $request->input('id');

        $requestId = RequestDetail::where('id', $id)->pluck('request_id');
        $requestBarang = RequestBarang::with('user','closedby','request_detail', 'request_detail.product', 'request_type', 'request_approval', 'request_approval.user')
        ->where('id', $requestId)
        ->get();

        return response()->json($requestBarang);
    }

    public function printrequestqr($id)
    {
        $pdf = App::make('dompdf.wrapper');
        $barcode = new DNS2D();
        $pdf->loadHTML($barcode->getBarcodeHTML(strval($id), 'QRCODE'));

        return $pdf->stream('requestqr.pdf');
    }
    
    public function productqr()
    {
        return view('auths.productqr');
    }
    
    public function searchProduct(Request $request)
    {
        $id = $request->input('id');
        
        $product = Product::with('unit_type','category')
        ->where('id', $id)
        ->get();
        
        return response()->json($product);
    }
    
    public function printproductqr($id)
    {
        $pdf = App::make('dompdf.wrapper');
        $barcode = new DNS2D();
        $pdf->loadHTML($barcode->getBarcodeHTML(strval($id), 'QRCODE'));

        return $pdf->stream('productqr.pdf');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
}
