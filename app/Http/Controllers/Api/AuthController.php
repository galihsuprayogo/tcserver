<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Msme;

class AuthController extends Controller
{
    public function signup(Request $req)
    {
        
        $validator = Validator::make($req->all(), [
            'name' => 'required|string',
            'phone_number' => 'required|string|min:12|max:15|unique:users',
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'Register Failed'
            ], 401);
        }

        $uf = User::factory()->make();

        $user = new User([
            'name' => $req->name,
            'phone_number' => $req->phone_number,
            'email' => $uf->email,
            'password' => $uf->password,
        ]);
        
        $user->save();

        $id = DB::table('users')->where('phone_number', $req->phone_number)->pluck('id');
        $new_id = $id['0'];
        
        $msme = new Msme([
            'user_id' => $new_id,
        ]);

        $msme->save();

        return response()->json([
            'message' => 'Register Success',
        ], 201);
    }

    public function login(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required|string',
            'phone_number' => 'required|string'
        ]);
        
        if($validator->fails()){
            return response()->json([
                'message' => 'Login Failed'
            ], 401);
        }

        if(DB::table('users')->where('phone_number', $req->phone_number)->exists()){

            $id = DB::table('users')->where('phone_number', $req->phone_number)->pluck('id');
            $new_id = $id['0'];
            $user = User::find($new_id);
            
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
    
            $token->save();
            return response()->json([
                'token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'user' => $user,
                'message' => 'Login Success'
            ]);

        } else {
            return response()->json([
                'message' => 'unregistered account'
            ], 401);   
        }
    }

    public function logout(Request $req)
    {
    
        $req->user()->token()->revoke();

        return response()->json([
            'message' => 'logout success',
        ]);   
    }
}
