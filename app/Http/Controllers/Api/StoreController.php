<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ImageRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\Store;


class StoreController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $name = $request->name;
        $image = $request->photo;
        $address = $request->address;      

        Store::where('user_id', $user->id)
                    ->update(['name' => $name,
                              'image' => $image,
                              'address' => $address]);
        $sid = DB::table('stores')->where('user_id', $user->id)->pluck('id');
        $new_sid = $sid['0'];
        $store = Store::find($new_sid);
        
        return response()->json([
            'message' => 'success',
            'user' => $user,
            'store' => $store
        ]);

    }

    public function image(Request $request)
    {
        $user = $request->user();
        $path = DB::table('stores')->where('user_id', $user->id)->pluck('image');
        $newPath = $path['0'];
      
        return response()->json([
            'message' => 'success',
            'encode' => $newPath
        ]);

    }
    
    public function show(Request $req)
    {
        $user = $req->user();
        $profile = DB::table('users')
                        ->join('stores', 'users.id', '=', 'stores.user_id')
                        ->select('stores.*')
                        ->where('users.id', $user->id)
                        ->get();

        return response()->json([
            'data' => $profile,
        ]);   
    }

    

    public function backupDecode()
    {
        // $image_decode = base64_decode($image);
        // $type = Str::substr($extend, 6);
        // $image_name = 'profile_store_'.$user->id.'.'.$type;
        // $newPath = public_path() . '/profile_store/';
        // File::makeDirectory($newPath, $mode = 0777, true, true);
        // $thumbPath = $newPath . $image_name;
        // image::make($image_decode)->save($thumbPath)->resize(100,100);
    }
}
