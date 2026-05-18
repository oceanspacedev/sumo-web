<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Exports\UserTemplateExport;
use App\Imports\UserImport;
use App\Models\Area;
use App\Models\BadanUsaha;
use App\Models\Divisi;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class UserController extends Controller
{
    public function profile(User $user)
    {
        return view('master.user.profile', [
            'user' => $user,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $users = User::with('division')->withTrashed()->paginate(30);

        if($request->has('search')){
            $users = User::with('division')
            ->where('fullname', 'LIKE', '%'.$request->search.'%')
            ->withTrashed()
            ->paginate(30);
        }

        return view('master.user.index')->with([
            'users' => $users,
            'division' => Divisi::orderBy('division')->get(),
            'roles' => Role::all(),
            'badan_usahas' => BadanUsaha::all(),
            'areas' => Area::all(),
            'approvals' => User::orderBy('fullname')->get(),
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
            $request['password'] = bcrypt($request->password);
            
            $user = User::create($request->all());

            $user->remember_token = Str::random(60);
            $user->save();

            $profile_picture = $this->storeImage($request);

            $user->profile_picture = $profile_picture;
            $user->save();

            return redirect('user')->with('success', 'Data berhasil diinput !');
        } catch (Exception $e) {
            return redirect('user')->with(['error' => $e->getMessage()]);
        }
    }

    public function storeImage(Request $request, $disk = 'public')
    {
        try {
            $this->validate($request, [
                'profile_picture' => 'required|file|image|mimes:jpeg,png,jpg|max:2048',
            ]);
    
            $file = $request->file('profile_picture');
            $date = Carbon::now()->format('Y-m-d');
            $fullname = $request->fullname;
            $extension = $file->getClientOriginalExtension();
            $path = 'profile';
            if (! Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->makeDirectory($path);
            }
    
            $filename = "Profile - ".$fullname." ".$date."_". time() .".".$extension;
    
            $file->storeAs($path, $filename, $disk);
    
            return $filename;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
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
    public function edit(User $user)
    {
        return view('master.user.edit')->with([
            'user' => $user,
            'division' => Divisi::all(),
            'areas' => Area::all(),
            'roles' => Role::all(),
            'badan_usahas' => BadanUsaha::all(),
            'approvals' => User::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        try {
            if ($request->profile_picture != null) {
                $profile_picture = $this->storeImage($request);
            }

            $user->update([
                'fullname' => $request->fullname,
                'username' => $request->username,
                'password' => bcrypt($request->password),
                'badan_usaha_id' => $request->badan_usaha_id,
                'division_id' => $request->division_id,
                'role_id' => $request->role_id,
                'approval_id' => $request->approval_id,
                'profile_picture' => $request->profile_picture == null ? $user->profile_picture : $profile_picture,
            ]);

            return redirect('user')->with('success', 'Data berhasil diupdate !');
        } catch (Exception $e) {
             throw new Exception($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        try {
            $user->delete($user);

            return redirect('user')->with('success', 'Data berhasil dihapus !');
        } catch (Exception $e) {
             return redirect('user')->with(['error' => $e->getMessage()]);
        }
    }

    public function active(Request $request, $id)
    {
        $user = User::onlyTrashed()->find($id);
        $user->deleted_at = null;
        $user->save();
        return redirect('user')->with(['success' => "Berhasil mengatifkan kembali user " . $user->fullname]);
    }

    public function export()
    {
        return Excel::download(new UserExport, 'user.xlsx');
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

        //$file->move(storage_path('import/'), $namaFile);
        Excel::import(new UserImport, storage_path('import/' . $namaFile));
        return redirect('user')->with(['success' => 'Berhasil import user']);
    }

    public function template()
    {
        return Excel::download(new UserTemplateExport, 'user_template.xlsx');
    }
}
