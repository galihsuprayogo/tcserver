<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Msme extends Model
{
    use HasFactory;

    protected $table = 'Msmes';
    protected $fillable = [
        'user_id',
        'name',
        'image',
        'latitude',
        'longitude',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
