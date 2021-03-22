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

    public function isAvailableStore(Request $request)
    {
        $consumer_id = $request->consumerId;
        $type = $request->type;
        $procedure = $request->procedure;
        $output = $request->output;
        $grade = $request->grade;
        $minimum = $request->minimum;
        $maximum = $request->maximum;
        $latitude_position = floatval($request->latitude);
        $longitude_position = floatval($request->longitude);

        $counts = DB::table('products')
                                ->join('stores', 'products.store_id', '=', 'stores.id')
                                ->select(DB::raw('count(*) as store_count, products.type'))
                                ->where([
                                    ['products.type', $type],
                                    ['products.procedure', $procedure],
                                    ['products.output', $output],
                                    ['products.grade', $grade],
                                    ['products.price', '>=', $minimum],
                                    ['products.price', '<=', $maximum]
                                ])
                                ->groupBy('products.type')
                                ->get();
        if($counts->count() === 0){
            return response()->json([
                'counts' => $counts,
                'message' => 'empty'
            ]);
        }else{
            if($counts[0]->store_count < 3){
                    return response()->json([
                        'counts' => $counts[0]->store_count,
                        'message' => 'unavailable'
                    ]);
                }else {
                    return response()->json([
                        'counts' => $counts[0]->store_count,
                        'message' => 'available'
                    ]);
            }
        }
    }

    public function indexPreferencesMultiCriteria(Request $request)
    {
        $this->distance($request);
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
                        $types[$data[$i]->store_id][$data[$j]->store_id]->push($this->calculatingAlternativeValue($types[$data[$i]->store_id][$data[$j]->store_id][2]));
                        $procedures[$data[$i]->store_id][$data[$j]->store_id]->push($this->calculatingAlternativeValue($procedures[$data[$i]->store_id][$data[$j]->store_id][2]));
                        $outputs[$data[$i]->store_id][$data[$j]->store_id]->push($this->calculatingAlternativeValue($outputs[$data[$i]->store_id][$data[$j]->store_id][2]));
                        $grades[$data[$i]->store_id][$data[$j]->store_id]->push($this->calculatingAlternativeValue($grades[$data[$i]->store_id][$data[$j]->store_id][2]));
                        $prices[$data[$i]->store_id][$data[$j]->store_id]->push($this->calculatingAlternativeValue($prices[$data[$i]->store_id][$data[$j]->store_id][2]));
                        $distances[$data[$i]->store_id][$data[$j]->store_id]->push($this->calculatingAlternativeValue($distances[$data[$i]->store_id][$data[$j]->store_id][2]));          
                    }
                }
        }

        for ($i=0; $i < sizeof($data) ; $i++) { 
            for ($j=0; $j < sizeof($data); $j++) { 
                if($i!==$j){
                    $tableMultiCriteria[$data[$i]->store_id][$data[$j]->store_id] = 
                    ($types[$data[$i]->store_id][$data[$j]->store_id][3] + 
                        $procedures[$data[$i]->store_id][$data[$j]->store_id][3] +
                        $outputs[$data[$i]->store_id][$data[$j]->store_id][3] +
                        $grades[$data[$i]->store_id][$data[$j]->store_id][3] +
                        $prices[$data[$i]->store_id][$data[$j]->store_id][3] +
                        $distances[$data[$i]->store_id][$data[$j]->store_id][3])/$totalCriteria;
                }
                if($i==$j){
                    $tableMultiCriteria[$data[$i]->store_id][$data[$j]->store_id] = 0;
                } 
            }
        }

        $leavingFlows = $this->leavingFlow($tableMultiCriteria, $totalAlternative, $data);  
        $enteringFlows = $this->enteringFlow($tableMultiCriteria, $totalAlternative, $data);
        $netFlows = $this->netFlow($leavingFlows, $enteringFlows, $tableMultiCriteria, $data);

        return response()->json([
            'message' => 'success'
        ]);

    }

    public function distance($request)
    {
        $consumer_id = $request->consumerId;
        $type = $request->type;
        $procedure = $request->procedure;
        $output = $request->output;
        $grade = $request->grade;
        $minimum = $request->minimum;
        $maximum = $request->maximum;
        $latitude_position = floatval($request->latitude);
        $longitude_position = floatval($request->longitude);

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
                                ])->get();
        
        
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

    public function leavingFlow($tableMultiCriteria, $totalAlternative, $data)
    {
        $bantu = array_keys($tableMultiCriteria);
        for ($i=0; $i < sizeof($tableMultiCriteria); $i++) { 
            $leaving[$data[$i]->store_id] = array_sum($tableMultiCriteria[$bantu[$i]])/$totalAlternative;
        }
        return $leaving;
    }

    public function enteringFlow($tableMultiCriteria, $totalAlternative, $data)
    {
        $bantu = array_keys($tableMultiCriteria);
        for ($i=0; $i < sizeof($tableMultiCriteria) ; $i++) { 
            for ($j=0; $j < sizeof($tableMultiCriteria); $j++) { 
                $entering[$data[$j]->store_id][$data[$i]->store_id]= $tableMultiCriteria[$bantu[$i]][$bantu[$j]];
            }
        }

        for ($i=0; $i < sizeof($tableMultiCriteria); $i++) { 
            $enteringFlowBackFlip[$data[$i]->store_id] = array_sum($entering[$bantu[$i]])/$totalAlternative;
        }
        return $enteringFlowBackFlip;
    }

    public function netFlow($leaving, $entering, $tableMultiCriteria, $data)
    {
        $bantu = array_keys($tableMultiCriteria);
        for ($i=0; $i < sizeof($tableMultiCriteria); $i++) { 
            $net[$data[$i]->store_id] = $leaving[$bantu[$i]] - $entering[$bantu[$i]];
            DB::table('promethees')->where('store_id', $data[$i]->store_id)->update([
                'score' => $leaving[$bantu[$i]] - $entering[$bantu[$i]]
            ]);
        }
        return $net;
    }

    public function ranking(Request $request)
    {
        $consumer_id = $request->consumerId;
        $ranking = DB::table('distances')
                        ->join('promethees', 'distances.id', '=', 'promethees.distance_id')
                        ->join('stores', 'promethees.store_id', '=', 'stores.id')
                        ->select('stores.id', 'stores.name', 'stores.image', 'distances.latitude', 
                          'distances.longitude', 'stores.address', 'promethees.score')
                        ->where('distances.consumer_id', $consumer_id)
                        ->orderBy('promethees.score', 'desc')
                        ->limit(5)
                        ->get();

        return response()->json([
            'ranking' => $ranking
        ]);
    }

    public function clearHelper(Request $request)
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
