<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

    public function indexPreferencesMultiCriteria(Request $request)
    {
        $consumer_id = $request->consumerId;
        $totalCriteria = 6;
        $data = DB::table('promethees')
                    ->join('distances', 'promethees.distance_id', '=', 'distances.id')
                    ->select('promethees.store_id', 'promethees.type', 'promethees.procedure', 'promethees.output', 
                    'promethees.grade', 'promethees.price', 'distances.distance')
                    ->where('distances.consumer_id', $consumer_id)
                    ->get();

        $totalAlternative = sizeof($data) - 1;
        for ($i=0; $i < sizeof($data); $i++) {
                for ($j=0; $j < sizeof($data); $j++) { 
                    if($i!==$j){
                        $types[$data[$i]->store_id][$data[$j]->store_id] = collect([$data[$i]->type, $data[$j]->type, ($data[$i]->type-$data[$j]->type)]);
                        $procedures[$data[$i]->store_id][$data[$j]->store_id] = collect([$data[$i]->procedure, $data[$j]->procedure, ($data[$i]->procedure-$data[$j]->procedure)]);
                        $outputs[$data[$i]->store_id][$data[$j]->store_id] = collect([$data[$i]->output, $data[$j]->output, ($data[$i]->output-$data[$j]->output)]);
                        $grades[$data[$i]->store_id][$data[$j]->store_id] = collect([$data[$i]->grade, $data[$j]->grade, ($data[$i]->grade-$data[$j]->grade)]);
                        $prices[$data[$i]->store_id][$data[$j]->store_id] = collect([$data[$i]->price, $data[$j]->price, ($data[$i]->price-$data[$j]->price)]);
                        $distances[$data[$i]->store_id][$data[$j]->store_id] = collect([$data[$i]->distance, $data[$j]->distance, ($data[$i]->distance-$data[$j]->distance)]);
                        $types[$data[$i]->store_id][$data[$j]->store_id]->push($this->calculatingAlternativeValue($types[$data[$i]->store_id][$data[$j]->store_id][sizeof($data)-1]));
                        $procedures[$data[$i]->store_id][$data[$j]->store_id]->push($this->calculatingAlternativeValue($procedures[$data[$i]->store_id][$data[$j]->store_id][sizeof($data)-1]));
                        $outputs[$data[$i]->store_id][$data[$j]->store_id]->push($this->calculatingAlternativeValue($outputs[$data[$i]->store_id][$data[$j]->store_id][sizeof($data)-1]));
                        $grades[$data[$i]->store_id][$data[$j]->store_id]->push($this->calculatingAlternativeValue($grades[$data[$i]->store_id][$data[$j]->store_id][sizeof($data)-1]));
                        $prices[$data[$i]->store_id][$data[$j]->store_id]->push($this->calculatingAlternativeValue($prices[$data[$i]->store_id][$data[$j]->store_id][sizeof($data)-1]));
                        $distances[$data[$i]->store_id][$data[$j]->store_id]->push($this->calculatingAlternativeValue($distances[$data[$i]->store_id][$data[$j]->store_id][sizeof($data)-1]));          
                    }
                }
        }

        for ($i=0; $i < sizeof($data) ; $i++) { 
            for ($j=0; $j < sizeof($data); $j++) { 
                if($i!==$j){
                    $tableMultiCriteria[$data[$i]->store_id][$data[$j]->store_id] = 
                    ($types[$data[$i]->store_id][$data[$j]->store_id][sizeof($data)] + 
                        $procedures[$data[$i]->store_id][$data[$j]->store_id][sizeof($data)] +
                        $outputs[$data[$i]->store_id][$data[$j]->store_id][sizeof($data)] +
                        $grades[$data[$i]->store_id][$data[$j]->store_id][sizeof($data)] +
                        $prices[$data[$i]->store_id][$data[$j]->store_id][sizeof($data)] +
                        $distances[$data[$i]->store_id][$data[$j]->store_id][sizeof($data)])/$totalCriteria;
                }
                if($i==$j){
                    $tableMultiCriteria[$data[$i]->store_id][$data[$j]->store_id] = 0;
                } 
            }
        }

        $leavingFlows = $this->leavingFlow($tableMultiCriteria, sizeof($data), $totalAlternative, $data);  
        $enteringFlows = $this->enteringFlow($tableMultiCriteria, sizeof($data), $totalAlternative, $data);
        $netFlows = collect([$this->netFlow($leavingFlows, $enteringFlows, sizeof($data), $data)]);

        return response()->json([
            'leavingFlow' => $leavingFlows,
            'enteringFlow' => $enteringFlows,
            'netFlow' => $netFlows->sortDesc()
        ]);
    }

    public function calculatingAlternativeValue($params)
    {
        switch ($params) {
            case $params === 0:
                return 0;
                break;
            case $params < 0:
                return 0;
                break;
            case $params > 0:
                return 1;
                break;
            default:
                return 0;
                break;
        }
    }

    public function leavingFlow($tableMultiCriteria, $size, $totalAlternative, $data)
    {
        for ($i=1; $i <= $size; $i++) { 
            $leaving[$data[$i-1]->store_id] = array_sum($tableMultiCriteria[$i])/$totalAlternative;
        }
       return $leaving;
    }

    public function enteringFlow($tableMultiCriteria, $size, $totalAlternative, $data)
    {
        for ($i=1; $i <= $size ; $i++) { 
            for ($j=1; $j <= $size; $j++) { 
                $entering[$data[$j-1]->store_id][$data[$i-1]->store_id]= $tableMultiCriteria[$i][$j];
            }
        }

        for ($i=1; $i <= $size; $i++) { 
            $enteringFlowBackFlip[$data[$i-1]->store_id] = array_sum($entering[$i])/$totalAlternative;
        }
        return $enteringFlowBackFlip;
    }

    public function netFlow($leaving, $entering, $size, $data)
    {
        for ($i=1; $i <= $size; $i++) { 
            $net[$data[$i-1]->store_id] = $leaving[$i] - $entering[$i];
        }
        return $net;
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
