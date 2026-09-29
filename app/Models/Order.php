<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'total_price', 'status', 'payment_method', 'is_paid', 'idempotency_key', 'scan_token', 'subtotal', 'discount_total', 'voucher_codes', 'voucher_amount', 'tax_amount', 'shipping_amount', 'contact_name', 'address', 'address_lat', 'address_lng'];

    protected function casts(): array
    {
        return ['voucher_codes' => 'array'];
    }

    // Cart-content key: same cart -> same key, any item change -> new key.
    public static function idempotencyKey($userId, $items, array $codes = [], string $destination = ''): string
    {
        $parts = $items->map(fn ($i) => $i->product_id.':'.($i->color_id ?? 0).':'.($i->size_id ?? 0).':'.$i->quantity.':'.$i->product->effectivePrice())
            ->sort()->values()->all();
        sort($codes);

        return hash('sha256', $userId.'|'.implode(',', $parts).'|'.implode(',', $codes).'|'.$destination);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
