<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = true;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, string $default = ''): string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }
}
