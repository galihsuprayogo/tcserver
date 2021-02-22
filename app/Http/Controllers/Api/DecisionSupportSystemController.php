<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Store;
use App\Models\Distance;

class DecisionSupportSystemController extends Controller
{
    public function minMax(Request $request)
    {
        $minimum = DB::table('products')->min('price');
        $maximum = DB::table('products')->max('price');

        return response()->json([
            'minimum' => $minimum,
            'maximum' => $maximum
        ]);
    }

    public function distance(Request $request)
    {
        $type = $request->type;
        $procedure = $request->procedure;
        $output = $request->output;
        $grade = $request->grade;
        $minimum = floatval($request->minimum);
        $maximum = floatval($request->maximum);
        $latitude_position = floatval($request->latitude);
        $longitude_position = floatval($request->longitude);

        $collects = DB::table('products')
                                ->join('stores', 'products.store_id', '=', 'stores.id')
                                ->select('products.store_id', 'stores.latitude', 'stores.longitude')
                                ->where([
                                    ['products.type', $type],
                                    ['products.procedure', $procedure],
                                    ['products.output', $output],
                                    ['products.grade', $grade],
                                    ['products.price', '>=', $minimum],
                                    ['products.price', '<=', $maximum]
                                ])
                                ->get();
        foreach ($collects as $collect) {
            Distance::insert([
                'store_id' => $collect->store_id,
                'latitude' => floatval($collect->latitude),
                'longitude'=> floatval($collect->longitude),
                'distance' => 0
            ]);
        }
        
        $destinations = Distance::all();

        foreach ($destinations as $destination) {
            $theta = $longitude_position - $destination->longitude;
            $miles = (sin(deg2rad($latitude_position)) * sin(deg2rad($destination->latitude))) 
            + (cos(deg2rad($latitude_position)) * cos(deg2rad($destination->latitude)) * cos(deg2rad($theta)));
            $miles = acos($miles);
            $miles = rad2deg($miles);
            $miles = $miles * 60 * 1.1515;
            $kilometers = round(($miles * 1.609344),1);
            $meters = $kilometers * 1000;

            DB::table('distances')->where('store_id', $destination->store_id)->update([
                'distance' => $kilometers
            ]);
        }

        $distances = Distance::select('distance')->get();
        return response()->json([
            'distances' => $distances
        ]);
    }

    public function clearDistance(Request $request)
    {
        DB::table('distances')->delete();
        return response()->json([
            'message' => 'clear success'
        ]);
    }
}
