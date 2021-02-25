<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promethee extends Model
{
    use HasFactory;

    protected $table = 'promethees';
    protected $fillable = [
        'store_id',
        'distance_id',
        'type',
        'procedure',
        'output',
        'grade',
        'price'
    ];
}
