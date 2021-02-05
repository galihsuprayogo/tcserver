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
        $profile = DB::table('users')
                        ->join('stores', 'users.id', '=', 'stores.user_id')
                        ->where('users.id', $user->id)
                        ->pluck('stores.id');
        $profile_id = $profile[0];

        $product = new Product([
            'type' => $request->type,
            'procedure' => $request->procedure,
            'output' => $request->output,
            'grade' => $request->grade,
            'price' => $request->price,
            'image' => $request->photo,
            'store_id' => (int) $profile_id
        ]);

        $product->save();

        return response()->json([
            'message' => 'add product success',
            'data' => $profile_id
        ]);
    }

    public function show(Request $req)
    {
        $user = $req->user();
        $profile = DB::table('users')
                        ->join('stores', 'users.id', '=', 'stores.user_id')
                        ->where('users.id', $user->id)
                        ->pluck('stores.id');
        $profile_id = $profile[0];

        $products = DB::table('products')
                        ->join('stores', 'products.store_id', '=', 'stores.id')
                        ->select('products.*')
                        ->where('stores.id', $profile_id)
                        ->paginate(1);

        return response()->json([
            'message' => 'success',
            'products' => $products
        ], 201);
    }
}
