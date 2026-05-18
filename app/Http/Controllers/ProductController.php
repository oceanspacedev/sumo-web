<?php

namespace App\Http\Controllers;

use App\Exports\ProductExport;
use App\Exports\ProductTemplateExport;
use App\Imports\ProductImport;
use App\Models\Category;
use App\Models\Product;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $products = Product::orderBy('product')->paginate(30);

        if($request->has('search')){
            $products = Product::with('category', 'unit_type')
            ->where('product', 'LIKE', '%'.$request->search.'%')
            ->withTrashed()
            ->paginate(30);
        }

        return view('master.product.index', [
            'products' => $products,
            'categories' => Category::all(),
            'unit_types' => UnitType::all(),
        ]);
    }

    public function get(Request $request)
    {
        $product = Product::with('unit_type','category')
        ->where('category_id', $request->req_type_id)
        ->orderBy('product')
        ->get();

        return response()->json($product);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $disk = 'public')
    {
        try {
            $product_image = null;
            
            if($request->product_image != null){
                $product_image = $this->storeImage($request);
            }

            $product = Product::create($request->all());
            $product->product_image = $product_image;
            $product->save();

            return redirect('product')->with('success', 'Data berhasil diinput !');
        } catch (Exception $e) {
            return redirect('product')->with(['error' => $e->getMessage()]);
        }
    }

    public function storeImage(Request $request, $disk = 'public')
    {
        try {
            $this->validate($request, [
                'product_image' => 'required|file|image|mimes:jpeg,png,jpg|max:2048',
            ]);
    
            $file = $request->file('product_image');
            $date = Carbon::now()->format('Y-m-d');
            $product_name = $request->product;
            $extension = $file->getClientOriginalExtension();
            $path = 'product';
            if (! Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->makeDirectory($path);
            }
    
            $filename = "Product - ".$product_name." ".$date."_". time() .".".$extension;
    
            $file->storeAs($path, $filename, $disk);
    
            return $filename;

        } catch (Exception $e) {
            return redirect('product')->with(['error' => $e->getMessage()]);
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
    public function edit(Product $product)
    {
        return view('master.product.edit', [
            'product' => $product,
            'categories' => Category::all(),
            'unit_types' => UnitType::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        try {             
            if ($request->product_image != null) {
                $product_image = $this->storeImage($request);
            }

            $product->update([
                'product' => $request->product,
                'category_id' => $request->category_id,
                'unit_type_id' => $request->unit_type_id,
                'price' => $request->price,
                'description' => $request->description,
                'stock' => $request->stock,
                'product_image' => $request->product_image == null ? $product->product_image : $product_image,
            ]);

            return redirect('product')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
            return redirect('product')->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        try {
            $this->deleteAssets($product);

            $product->delete($product);

            return redirect('product')->with('success', 'Data dan foto berhasil dihapus !');
        } catch (Exception $e) {
            return redirect('product')->with(['error' => $e->getMessage()]);
        }
    }

    public function checkAssets($path)
    {
        if ($path == null) {
            return false;
        }

        return (file_exists(storage_path('app/public/product/' . $path)));
    }

    public function deleteAssets($product)
    {
        if ($this->checkAssets($product->product_image)) {
           
            unlink(storage_path('app/public/product/'.$product->product_image));
            
        }
    }

    public function export()
    {
        return Excel::download(new ProductExport, 'barang.xlsx');
    }

    public function import(Request $request, $disk = 'public')
    {
        $file = $request->file('fileImport');
        $namaFile = $file->getClientOriginalName();

        $path = 'import';
        if (! Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->makeDirectory($path);
        }
        $file->storeAs($path, $namaFile, $disk);

        $file->move(storage_path('import/'), $namaFile);
        Excel::import(new ProductImport, storage_path('import/' . $namaFile));
        
        return redirect('product')->with(['success' => 'Berhasil import data barang !']);
    }

    public function template()
    {
        return Excel::download(new ProductTemplateExport, 'barang_template.xlsx');
    }
}
