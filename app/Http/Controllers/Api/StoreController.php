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
use App\Models\User;


class StoreController extends Controller
{
    public function store(Request $request)
    {
        $old_user = $request->user();
        $store_name = $request->store_name;
        $image = $request->photo;
        $address = $request->address;
        $latitude = $request->latitude;
        $longitude = $request->longitude;      
        $name = $request->name;
        $phone_number = $request->phone_number;

        Store::where('user_id', $old_user->id)
                    ->update(['name' => $store_name,
                              'image' => $image,
                              'address' => $address,
                              'latitude' => $latitude,
                              'longitude' => $longitude]);
        
        User::where('id', $old_user->id)
                    ->update(['name' => $name,
                              'phone_number' => $phone_number]);
        
        $sid = DB::table('stores')->where('user_id', $old_user->id)->pluck('id');
        $new_sid = $sid['0'];
        $store = Store::find($new_sid);
        $user = User::find($old_user->id);
        
        return response()->json([
            'message' => 'success',
            'user' => $user,
            'store' => $store
        ]);

    }
}
