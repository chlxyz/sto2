<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'min_subtotal',
        'expires_at', 'max_uses', 'used_count', 'is_active',
        'max_discount', 'exclude_sale_items',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function usable(float $subtotal): ?string
    {
        if (! $this->is_active) {
            return 'Voucher is not active.';
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'Voucher has expired.';
        }
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return 'Voucher usage limit reached.';
        }
        if ($subtotal < (float) $this->min_subtotal) {
            return 'Order total is below the voucher minimum.';
        }

        return null;
    }
}
