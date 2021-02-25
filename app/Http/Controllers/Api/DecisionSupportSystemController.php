<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Store;
use App\Models\Distance;
use App\Models\Promethee;

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
        $consumer_id = $request->consumerId;
        $type = $request->type;
        $procedure = $request->procedure;
        $output = $request->output;
        $grade = $request->grade;
        $minimum = floatval($request->minimum);
        $maximum = floatval($request->maximum);
        $latitude_position = floatval($request->latitude);
        $longitude_position = floatval($request->longitude);

        if($grade === 'Default'){
            $collects = DB::table('products')
                                ->join('stores', 'products.store_id', '=', 'stores.id')
                                ->select('products.type', 'products.procedure', 'products.output', 
                                'products.grade', 'products.price', 
                                'stores.id', 'stores.latitude', 'stores.longitude')
                                ->where([
                                    ['products.type', $type],
                                    ['products.procedure', $procedure],
                                    ['products.output', $output],
                                    ['products.price', '>=', $minimum],
                                    ['products.price', '<=', $maximum]
                                ])
                                ->get();
        } else {
            $collects = DB::table('products')
                                ->join('stores', 'products.store_id', '=', 'stores.id')
                                ->select('products.type', 'products.procedure', 'products.output', 
                                'products.grade', 'products.price', 
                                'stores.id', 'stores.latitude', 'stores.longitude')
                                ->where([
                                    ['products.type', $type],
                                    ['products.procedure', $procedure],
                                    ['products.output', $output],
                                    ['products.grade', $grade],
                                    ['products.price', '>=', $minimum],
                                    ['products.price', '<=', $maximum]
                                ])
                                ->get();
        }
        
        foreach ($collects as $collect) {
            Distance::insert([
                'consumer_id' => $consumer_id,
                'latitude' => floatval($collect->latitude),
                'longitude'=> floatval($collect->longitude),
                'distance' => 0
            ]);

            Promethee::insert([
                'store_id' => $collect->id,
                'type' => $this->initialType($collect->type),
                'procedure' => $this->initialProcedure($collect->procedure),
                'output' => $this->initialOutput($collect->output),
                'grade' => $this->initialGrade($collect->grade),
                'price' => $collect->price
            ]);
        }

        
        $proms = Promethee::all();
        $gapes = Distance::all();
        foreach ($proms as $promskey => $prom) {
            foreach ($gapes as $gapeskey => $gap) {
                if($promskey === $gapeskey){
                    DB::table('promethees')->where('id', $prom->id)->update([
                        'distance_id' => $gap->id
                    ]);

                }
            }
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

            DB::table('distances')->where([
                ['id', $destination->id],
                ['consumer_id', $consumer_id],
            ])->update([
                'distance' => $kilometers
            ]);
        }

        $distances = Distance::select('distance')->get();
        return response()->json([
            'distances' => $distances,
        ]);
    }

    public function clearDistance(Request $request)
    {
        $consumer_id = $request->consumerId;
        Distance::where('consumer_id', $consumer_id)->delete();
        return response()->json([
            'message' => 'clear success'
        ]);
    }

    public function initialType($type)
    {
        switch ($type) {
            case 'Arabica':
                return 5;
                break;
            case 'Robusta':
                return 5;
                break;
            default:
                return 5;
                break;
        }
    }

    public function initialProcedure($procedure)
    {
        switch ($procedure) {
            case 'Fullwash':
                return 5;
                break;
            case 'Semiwash':
                return 5;
                break;
            default:
                return 5;
                break;
        }
    }

    public function initialOutput($output)
    {
        switch ($output) {
            case 'Green Bean':
                return 5;
                break;
            case 'Roasted Bean':
                return 5;
                break;
            default:
                return 5;
                break;
        }
    }

    public function initialGrade($grade)
    {
        switch ($grade) {
            case 'A':
                return 5;
                break;
            case 'B':
                return 4;
                break;
            default:
                return 3;
                break;
        }
    }
}
