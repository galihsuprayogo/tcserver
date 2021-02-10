<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $fillable = [
        'store_id',
        'type',
        'procedure',
        'output',
        'grade',
        'price',
        'image'
    ];

    public function store()
    {
        return $this->belongsTo(User::class);
    }
}
