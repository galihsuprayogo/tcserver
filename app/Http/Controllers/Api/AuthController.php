<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Store;
// use Twilio;

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
        
        $store = new Store([
            'user_id' => $new_id,
        ]);

        $store->save();

        return response()->json([
            'message' => 'Register Success',
        ], 201);
    }

    public function login(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'phone_number' => 'required|string'
        ]);
        
        if($validator->fails()){
            return response()->json([
                'message' => 'Login Failed'
            ], 401);
        }

        if(DB::table('users')->where('phone_number', $req->phone_number)->exists()){
  
            $otp = mt_rand(1000, 9999);
            // Twilio::message('+62'.(int)$req->phone_number, $otp);
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
                'message' => 'Login Failed',
            ], 200);
        }

        if(DB::table('users')->where('phone_otp', $req->phone_otp)->exists())
        {
            $id = DB::table('users')->where('phone_otp', $req->phone_otp)->pluck('id');
            $new_id = $id['0'];
            $user = User::find($new_id);

            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
    
            $token->save(); 
            
            if (DB::table('sessions')->where('user_id', $user->id)->exists()){
                $session = DB::table('sessions')->where('user_id', $user->id)->update([
                        'user_session' => true
                ]);
            } else {
                $session = DB::table('sessions')->insert([
                    'user_id' => $user->id,
                    'user_session' => true
                ]);
            }

            return response()->json([
                'token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'user' => $user,
                'message' => 'Login Success',
                'phone_otp' => $user->phone_otp,
            ]);
            
        } else {
            return response()->json([
                'message' => 'unregistered otp',
            ], 201); 
        }
    }

    public function session(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'user_id' => 'required|string|min:1',
        ]);

        if($validator->fails()){
            return response()->json([
                'session' => 0,
            ], 200);
        }

        if(DB::table('sessions')->where('user_id', $req->user_id)->exists())
        {
            $session = DB::table('sessions')->where('user_id', $req->user_id)->pluck('user_session');
            $new_session = $session['0'];

            return response()->json([
                'session' => $new_session,
                'status' => true
            ], 200);
        } else {
            return response()->json([
                'session' => 0,
            ], 200);
        }
    }

    public function logout(Request $req)
    {
        $user = $req->user();
        DB::table('users')->where('phone_number', $user->phone_number)
        ->update(['phone_otp' => null]);
        DB::table('sessions')->where('user_id', $user->id)->update([
            'user_session' => false
         ]);
        $req->user()->token()->revoke();

        return response()->json([
            'message' => 'logout success',
        ]);   
    }
}
