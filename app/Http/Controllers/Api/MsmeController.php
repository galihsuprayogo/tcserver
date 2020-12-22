<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\Msme;


class MsmeController extends Controller
{
    public function show(Request $req)
    {
        $user = $req->user();
        $profile = DB::table('users')
                        ->join('msmes', 'users.id', '=', 'msmes.user_id')
                        ->select('msmes.*')
                        ->where('users.id', $user->id)
                        ->get();

        return response()->json([
            'data' => $profile,
        ]);   
    }

    public function store(Request $req)
    {
        $user = $req->user();
        $image_file = $req->image;
       
        $image_name = $image_file->getClientOriginalName();
        $newPath = public_path() . '/profile_msme/';
        File::makeDirectory($newPath, $mode = 0777, true, true);
        $thumbPath = $newPath . $image_name;
        $thumbImage = Image::make($image_file)->save($thumbPath)->resize(100, 100);

        Msme::where('user_id', $user->id)
                    ->update([  'name' => $req->name,
                                'image' => $thumbPath   ]);
        
        return $thumbImage->response('jpg');  
    }

    public function image(Request $req)
    {
        $user = $req->user();
        $path = DB::table('msmes')->where('user_id', $user->id)->pluck('image');
        $newPath = $path['0'];
        $image = Image::make($newPath);
        $response = Response::make($image->encode('jpg'));
        $response->header('Content-Type', 'image/jpeg');
        return $response;
    }
}
