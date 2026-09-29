<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id', 'label', 'contact_name', 'address', 'address_lat', 'address_lng',
    ];
}
