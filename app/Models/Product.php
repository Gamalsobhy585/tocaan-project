<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'code',
        'quantity_in_stock',
        'unit_price',
    ];

    protected $casts = [
        'quantity_in_stock' => 'integer',
        'unit_price' => 'decimal:2',
    ];
}