<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distance extends Model
{
    use HasFactory;

    protected $table = 'distances';
    protected $fillable = [
        'consumer_id',
        'latitude',
        'longitude',
        'distance'
    ];
}
