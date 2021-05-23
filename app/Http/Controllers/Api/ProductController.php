<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Collection;
use App\Models\Product;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $type = $request->type;
        $procedure = $request->procedure;
        $output = $request->output;
        $grade = $request->grade;
        $price = $request->price;
        $photo = $request->photo;

        $profile_address = DB::table('petanikopi')
                        ->join('stores', 'petanikopi.id', '=', 'stores.user_id')
                        ->where('petanikopi.id', $user->id)
                        ->pluck('stores.address');

        $profile_address_new = $profile_address[0];

        $profile = DB::table('petanikopi')
                            ->join('stores', 'petanikopi.id', '=', 'stores.user_id')
                            ->where('petanikopi.id', $user->id)
                            ->pluck('stores.id');

        $profile_id = $profile[0];

        $isExistProduct = Product::where('type', '=', $type)
                            ->where('procedure', '=', $procedure)
                            ->where('output', '=', $output)
                            ->where('grade', '=', $grade)
                            ->where('store_id', $profile_id)
                            ->exists();

        if(is_null($profile_address_new)){
            return response()->json([
                'address' => $profile_address_new,
            ]);
        } 
        elseif($isExistProduct){
            return response()->json([
                'address' => $profile_address_new,
                'isExist' => $isExistProduct,
            ]);
        }
        else{
            $checkRow = DB::table('products')
            ->where('store_id', $profile_id)
            ->pluck('type')
            ->first();

            if(is_null($checkRow)){
                Product::where('store_id', $profile_id)
                        ->update(['type' => $type,
                                'procedure' => $procedure,
                                'output' => $output,
                                'grade' => $grade,
                                'price' => $price,
                                'image' => $photo]);
            } else {
                $product = new Product([
                        'store_id' => (int) $profile_id,
                        'type' => $type,
                        'procedure' => $procedure,
                        'output' => $output,
                        'grade' => $grade,
                        'price' => $price,
                        'image' => $photo,
                ]);

                $product->save();
            }

            $products = DB::table('products')
                            ->join('stores', 'products.store_id', '=', 'stores.id')
                            ->select('products.*')
                            ->where('stores.id', $profile_id)
                            ->get();

            return response()->json([
                'address' => $profile_address_new,
                'isExist' => $isExistProduct,
                'message' => 'add product success',
                'products' => $products,
            ]);
        }
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $user = $request->user();
        $profile = DB::table('petanikopi')
                        ->join('stores', 'petanikopi.id', '=', 'stores.user_id')
                        ->where('petanikopi.id', $user->id)
                        ->pluck('stores.id');
        $profile_id = $profile[0];

        $is_row = DB::table('products')
                        ->select(DB::raw('count(*) as product_count, store_id'))
                        ->where('store_id', $profile_id)
                        ->groupBy('store_id')
                        ->get();

        if($is_row[0]->product_count === 1) {
            Product::where('id', $id)
                    ->update(['type' => null,
                              'procedure' => null,
                              'output' => null,
                              'grade' => null,
                              'price' => null,
                              'image' => null]);
        } else {
            Product::find($id)->delete();
        }
        
        $products = DB::table('products')
                            ->join('stores', 'products.store_id', '=', 'stores.id')
                            ->select('products.*')
                            ->where('stores.id', $profile_id)
                            ->get();

        return response()->json([
            'message' => 'delete product success',
            'products' => $products,
        ]);
    }
    
    public function modify(Request $request)
    {
        $id = $request->id;
        $isExistProduct = Product::where('type', '=', $request->type)
                            ->where('procedure', '=', $request->procedure)
                            ->where('output', '=', $request->output)
                            ->where('grade', '=', $request->grade)
                            ->Where('price', '=', $request->price)
                            ->where('store_id', $request->store_id)
                            ->exists();

        if($isExistProduct)
        {
            return response()->json([
                'isExist' => $isExistProduct,
            ]);
        }
        else 
        {
            Product::where('id', $id)
            ->update([
                'type' => $request->type,
                'procedure' => $request->procedure,
                'output' => $request->output,
                'grade' => $request->grade,
                'price' => $request->price,
                'image' => $request->photo
            ]);

            $user = $request->user();
            $profile = DB::table('petanikopi')
                                ->join('stores', 'petanikopi.id', '=', 'stores.user_id')
                                ->where('petanikopi.id', $user->id)
                                ->pluck('stores.id');
            $profile_id = $profile[0];

            $products = DB::table('products')
                                ->join('stores', 'products.store_id', '=', 'stores.id')
                                ->select('products.*')
                                ->where('stores.id', $profile_id)
                                ->get();

            return response()->json([
                'message' => 'update product success',
                'products' => $products,
                'isExist' => $isExistProduct,
            ]);
        }
    }

    public function isExist(Request $request)
    {
        $user = $request->user();
        $type = $request->type;
        $procedure = $request->procedure;
        $output = $request->output;
        $grade = $request->grade;
        $price = $request->price;
        $image = $request->photo;
    
        $profile = DB::table('petanikopi')
                        ->join('stores', 'petanikopi.id', '=', 'stores.user_id')
                        ->where('petanikopi.id', $user->id)
                        ->pluck('stores.id');
        $profile_id = $profile[0];

        $product = Product::where('type', '=', $type)
                            ->where('procedure', '=', $procedure)
                            ->where('output', '=', $output)
                            ->where('grade', '=', $grade)
                            ->where('store_id', $profile_id)
                            ->exists();

        return response()->json([
            'message' => 'already exist',
            'ket' => $product
        ]);
    }
}
