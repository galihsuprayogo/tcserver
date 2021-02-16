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
}
