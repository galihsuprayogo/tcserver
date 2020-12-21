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
use Twilio;

class AuthController extends Controller
{
    public function signup(Request $req)
    {
        
        $validator = Validator::make($req->all(), [
            'name' => 'required|string',
            'phone_number' => 'required|string|min:10|max:15|unique:users',
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
  
            $otp = mt_rand(1000, 9999);
            Twilio::message('+62'.(int)$req->phone_number, $otp);
            DB::table('users')->where('phone_number', $req->phone_number)->update(['phone_otp' => $otp]);

            return response()->json([
                'message' => 'Login Success'
            ]);

        } else {
            return response()->json([
                'message' => 'unregistered account'
            ], 401);   
        }
    }

    public function verify(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'phone_otp' => 'required|string|min:4',
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'Login Failed'
            ], 401);
        }

        if(DB::table('users')->where('phone_otp', $req->phone_otp)->exists())
        {
            $id = DB::table('users')->where('phone_otp', $req->phone_otp)->pluck('id');
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
                'message' => 'unregistered otp'
            ], 401); 
        }
    }

    public function logout(Request $req)
    {
        $user = $req->user();
        DB::table('users')->where('phone_number', $user->phone_number)->update(['phone_otp' => null]);
        $req->user()->token()->revoke();

        return response()->json([
            'message' => 'logout success',
        ]);   
    }
}
