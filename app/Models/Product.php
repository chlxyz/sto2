<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_photo',
        'product_name',
        'product_desc',
        'product_quantity',
        'product_price',
        'product_type',
    ];
}
