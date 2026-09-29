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
        'discount_percent',
        'released_at',
        'is_listed',
        'position',
    ];

    protected function casts(): array
    {
        return ['released_at' => 'datetime'];
    }

    public function types()
    {
        return $this->belongsToMany(Type::class, 'product_type');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function colors()
    {
        return $this->belongsToMany(Color::class, 'color_product')->withPivot('image_path');
    }

    public function sizes()
    {
        return $this->belongsToMany(Size::class, 'product_size')->withPivot('stock');
    }

    public function sizeChart()
    {
        return $this->hasOne(SizeChart::class);
    }

    // Sellable units: size-stock sum when sized, base quantity otherwise.
    public function stockAvailable(): int
    {
        if ($this->relationLoaded('sizes') ? $this->sizes->isNotEmpty() : $this->sizes()->exists()) {
            return $this->sizes->sum('pivot.stock');
        }

        return (int) $this->product_quantity;
    }

    public function effectivePrice(): float
    {
        return round((float) $this->product_price * (1 - ($this->discount_percent ?? 0) / 100), 2);
    }

    public function isPreorder(): bool
    {
        return $this->released_at && $this->released_at->isFuture();
    }
}
