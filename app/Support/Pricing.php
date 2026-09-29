<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\Voucher;

class Pricing
{
    // $lines: [{unit, list, quantity, sale}] with effective unit prices.
    // $codes: voucher code strings. Returns a full breakdown; throws on invalid voucher.
    public static function quote($lines, array $codes = []): array
    {
        $lines = collect($lines);
        $subtotal = round($lines->sum(fn ($l) => $l['unit'] * $l['quantity']), 2);
        $listTotal = round($lines->sum(fn ($l) => $l['list'] * $l['quantity']), 2);
        $discount = round(max(0, $listTotal - $subtotal), 2);
        $eligibleTotal = round($lines->reject(fn ($l) => $l['sale'] ?? false)->sum(fn ($l) => $l['unit'] * $l['quantity']), 2);

        $remainder = $subtotal;
        $eligibleRemainder = $eligibleTotal;
        $applied = [];
        foreach (array_unique($codes) as $code) {
            $voucher = Voucher::where('code', $code)->first();
            if (! $voucher) {
                throw new \InvalidArgumentException("Voucher {$code} does not exist.");
            }
            $scoped = (bool) $voucher->exclude_sale_items;
            $base = $scoped ? $eligibleRemainder : $remainder;
            if ($scoped && $base <= 0) {
                throw new \InvalidArgumentException("Voucher {$code} does not apply to sale items in your cart.");
            }
            if ($error = $voucher->usable($base)) {
                throw new \InvalidArgumentException("Voucher {$code}: {$error}");
            }
            if ($voucher->type === 'percent') {
                $amount = round($base * ((float) $voucher->value / 100), 2);
                if ($voucher->max_discount !== null) {
                    $amount = min($amount, (float) $voucher->max_discount);
                }
            } else {
                $amount = min((float) $voucher->value, $base);
            }
            $amount = round($amount, 2);
            $remainder = round($remainder - $amount, 2);
            $eligibleRemainder = round(max(0, $eligibleRemainder - $amount), 2);
            $applied[] = ['code' => $voucher->code, 'amount' => $amount, 'scoped' => $scoped];
        }
        $voucherAmount = round(array_sum(array_column($applied, 'amount')), 2);

        $tax = round($remainder * ((float) Setting::get('tax_percent', '0') / 100), 2);
        $shipping = 0.0;
        if ($remainder > 0) {
            $shipping = $remainder >= (float) Setting::get('free_shipping_min', PHP_FLOAT_MAX)
                ? 0.0
                : (float) Setting::get('shipping_flat', '0');
        }

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'eligible_subtotal' => $eligibleTotal,
            'vouchers' => $applied,
            'voucher_amount' => $voucherAmount,
            'tax_amount' => $tax,
            'shipping_amount' => round($shipping, 2),
            'total' => round($remainder + $tax + $shipping, 2),
        ];
    }
}
