<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}
