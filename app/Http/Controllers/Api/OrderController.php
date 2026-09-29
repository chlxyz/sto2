<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public static function presentOrder($order): array
    {
        return [
            'id' => $order->id,
            'total_price' => (float) $order->total_price,
            'subtotal' => (float) ($order->subtotal ?? $order->total_price),
            'discount_total' => (float) ($order->discount_total ?? 0),
            'voucher_codes' => $order->voucher_codes ?? [],
            'voucher_amount' => (float) ($order->voucher_amount ?? 0),
            'tax_amount' => (float) ($order->tax_amount ?? 0),
            'shipping_amount' => (float) ($order->shipping_amount ?? 0),
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'is_paid' => (bool) $order->is_paid,
            'idempotency_key' => $order->idempotency_key,
            'created_at' => $order->created_at,
            'items' => $order->orderItems->map(fn ($item) => [
                'product_name' => $item->product->product_name,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'color' => $item->color?->name,
                'size' => $item->size_label ?? $item->size?->label,
                'image_url' => $item->product->product_photo
                    ? asset('storage/'.$item->product->product_photo)
                    : null,
            ]),
        ];
    }

    public function index(Request $request)
    {
        $orders = Order::with(['orderItems.product', 'orderItems.color', 'orderItems.size'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn ($order) => self::presentOrder($order));

        return response()->json($orders);
    }

    public function destroy(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            abort(404);
        }

        if (! in_array($order->status, ['pending', 'prepared'])) {
            return response()->json(['message' => 'Order can no longer be cancelled.'], 422);
        }

        // Restock unpaid cancels so stock doesn't leak (paid cancels keep stock moved).
        if (! $order->is_paid) {
            $order->loadMissing(['orderItems.product.sizes']);
            foreach ($order->orderItems as $item) {
                if ($item->size_id) {
                    $row = $item->product?->sizes->firstWhere('id', $item->size_id);
                    $row && $item->product->sizes()->updateExistingPivot($item->size_id, [
                        'stock' => (int) $row->pivot->stock + $item->quantity,
                    ]);
                } else {
                    $item->product?->increment('product_quantity', $item->quantity);
                }
            }
        }

        $order->delete();

        return response()->json(null, 204);
    }
}
