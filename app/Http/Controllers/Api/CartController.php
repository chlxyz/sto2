<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    use PresentsProducts;

    private function lineStock($product, $sizeId): int
    {
        if ($sizeId) {
            $row = $product->sizes->firstWhere('id', $sizeId);
            return $row ? (int) $row->pivot->stock : 0;
        }

        return $product->stockAvailable();
    }

    public function index(Request $request)
    {
        $items = CartItem::with(['product.types', 'product.images', 'product.colors', 'product.sizes', 'product.sizeChart', 'color', 'size'])
            ->where('user_id', $request->user()->id)
            // Newest first. Keyed on id, not created_at: id is serial so it
            // matches insertion order exactly and cannot tie, whereas two adds
            // in the same second share a created_at and come back unordered.
            ->latest('id')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'product' => self::presentProduct($item->product),
                'color' => $item->color ? ['id' => $item->color->id, 'name' => $item->color->name, 'hex' => $item->color->hex] : null,
                'size' => $item->size ? ['id' => $item->size->id, 'label' => $item->size->label] : null,
                'line_total' => round($item->product->effectivePrice() * $item->quantity, 2),
            ]);

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'sometimes|integer|min:1',
            'color_id' => 'sometimes|nullable|integer|exists:colors,id',
            'size_id' => 'sometimes|nullable|integer|exists:sizes,id',
        ]);

        $product = \App\Models\Product::with(['colors', 'sizes'])->findOrFail($data['product_id']);

        if (! $product->is_listed) {
            return response()->json(['message' => 'Product is no longer available.'], 422);
        }

        $colorId = $data['color_id'] ?? null;
        if ($colorId && ! $product->colors->contains('id', $colorId)) {
            return response()->json(['message' => 'Invalid color for this product.'], 422);
        }

        $sizeId = $data['size_id'] ?? null;
        if ($product->sizes->isNotEmpty() && ! $sizeId) {
            return response()->json(['message' => 'Please choose a size.'], 422);
        }
        if ($sizeId && ! $product->sizes->contains('id', $sizeId)) {
            return response()->json(['message' => 'Invalid size for this product.'], 422);
        }

        $stock = $this->lineStock($product, $sizeId);
        if ($stock <= 0) {
            return response()->json(['message' => 'Product is out of stock.'], 422);
        }

        $item = CartItem::where('user_id', $request->user()->id)
            ->where('product_id', $data['product_id'])
            ->where('color_id', $colorId)
            ->where('size_id', $sizeId)
            ->first();

        // Clamp to stock: cart can never hold more than available.
        $quantity = min(($item?->quantity ?? 0) + ($data['quantity'] ?? 1), $stock);

        if ($item) {
            $item->update(['quantity' => $quantity]);
        } else {
            $item = CartItem::create([
                'user_id' => $request->user()->id,
                'product_id' => $data['product_id'],
                'color_id' => $colorId,
                'size_id' => $sizeId,
                'quantity' => $quantity,
            ]);
        }

        return response()->json($item->load(['product', 'color', 'size']), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'color_id' => 'sometimes|nullable|integer|exists:colors,id',
            'size_id' => 'sometimes|nullable|integer|exists:sizes,id',
        ]);

        $item = CartItem::where('user_id', $request->user()->id)->findOrFail($id);
        $item->loadMissing(['product.colors', 'product.sizes']);

        $colorId = array_key_exists('color_id', $data) ? $data['color_id'] : $item->color_id;
        $sizeId = array_key_exists('size_id', $data) ? $data['size_id'] : $item->size_id;

        if ($colorId && ! $item->product->colors->contains('id', $colorId)) {
            return response()->json(['message' => 'Invalid color for this product.'], 422);
        }

        if ($item->product->sizes->isNotEmpty() && ! $sizeId) {
            return response()->json(['message' => 'Please choose a size.'], 422);
        }
        if ($sizeId && ! $item->product->sizes->contains('id', $sizeId)) {
            return response()->json(['message' => 'Invalid size for this product.'], 422);
        }

        // Stock of the *new* variant, not the old line's.
        $stock = $this->lineStock($item->product, $sizeId);
        if ($stock <= 0) {
            return response()->json(['message' => 'Product is out of stock.'], 422);
        }

        $quantity = min($data['quantity'], $stock);

        // Switching onto a variant already in the cart folds into that line
        // instead of leaving a duplicate row for the same (product,color,size).
        // Two statements, deliberately not transactional: pooled pgsql rejects
        // multi-statement transactions (see UsesTestDatabase).
        $twin = CartItem::where('user_id', $item->user_id)
            ->where('product_id', $item->product_id)
            ->where('color_id', $colorId)
            ->where('size_id', $sizeId)
            ->where('id', '!=', $item->id)
            ->first();

        if ($twin) {
            $twin->update(['quantity' => min($twin->quantity + $quantity, $stock)]);
            $item->delete();

            return response()->json($twin->load(['product', 'color', 'size']));
        }

        $item->update(['quantity' => $quantity, 'color_id' => $colorId, 'size_id' => $sizeId]);

        return response()->json($item->load(['product', 'color', 'size']));
    }

    public function destroy(Request $request, $id)
    {
        $item = CartItem::where('user_id', $request->user()->id)->findOrFail($id);
        $item->delete();

        return response()->json(null, 204);
    }

    private function pricedLines($items): array
    {
        return $items->map(fn ($i) => [
            'unit' => $i->product->effectivePrice(),
            'list' => (float) $i->product->product_price,
            'quantity' => $i->quantity,
            'sale' => $i->product->effectivePrice() < (float) $i->product->product_price,
        ])->all();
    }

    // Quote-only preview for the checkout page (no order created).
    public function summary(Request $request)
    {
        $data = $request->validate(['voucher_codes' => 'sometimes|array', 'voucher_codes.*' => 'string']);

        $items = CartItem::with('product')
            ->where('user_id', $request->user()->id)
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        try {
            $quote = Pricing::quote($this->pricedLines($items), $data['voucher_codes'] ?? []);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($quote);
    }

    // Free orders skip payment entirely: nothing to charge, nothing to scan.
    private function settleFreeOrder(Order $order, $userId): Order
    {
        if ((float) $order->total_price === 0.0 && ! $order->is_paid) {
            $order->update(['is_paid' => true, 'status' => 'prepared', 'payment_method' => $order->payment_method ?? 'Free']);
            Activity::firstOrCreate(
                ['order_id' => $order->id, 'activity_type' => 'payment'],
                ['user_id' => $userId, 'details' => 'Free order — no payment required'],
            );
        }

        return $order;
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'voucher_codes' => 'sometimes|array',
            'voucher_codes.*' => 'string',
            'contact_name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'address_lat' => 'sometimes|nullable|numeric|between:-90,90',
            'address_lng' => 'sometimes|nullable|numeric|between:-180,180',
        ]);
        $codes = array_values(array_unique($data['voucher_codes'] ?? []));

        $items = CartItem::with(['product.sizes', 'product.colors'])
            ->where('user_id', $request->user()->id)
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        if ($items->first(fn ($i) => ! $i->product->is_listed)) {
            return response()->json(['message' => 'An item is no longer available. Please review your cart.'], 422);
        }

        // Guarded single-statement stock helpers (atomic in Postgres, pooler-safe).
        // Defined here, used after the replay check so replays never reserve twice.
        $reserve = function ($pid, $sid, $qty): bool {
            if ($sid) {
                return (bool) DB::table('product_size')
                    ->where('product_id', $pid)
                    ->where('size_id', $sid)
                    ->where('stock', '>=', $qty)
                    ->decrement('stock', $qty);
            }

            return (bool) DB::table('products')
                ->where('id', $pid)
                ->where('product_quantity', '>=', $qty)
                ->decrement('product_quantity', $qty);
        };
        $restore = function ($pid, $sid, $qty): void {
            if ($sid) {
                DB::table('product_size')
                    ->where('product_id', $pid)
                    ->where('size_id', $sid)
                    ->increment('stock', $qty);
            } else {
                DB::table('products')->where('id', $pid)->increment('product_quantity', $qty);
            }
        };

        try {
            $quote = Pricing::quote($this->pricedLines($items), $codes);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $key = Order::idempotencyKey($request->user()->id, $items, $codes, $data['contact_name'].$data['address']);

        // Replay: same cart checked out twice (double-click, closed page) returns the pending order.
        $existing = Order::where('user_id', $request->user()->id)
            ->where('idempotency_key', $key)
            ->where('is_paid', false)
            ->latest()->first();

        if ($existing) {
            $existing = $this->settleFreeOrder($existing, $request->user()->id);

            return response()->json($existing->load('orderItems.product'), 200);
        }

        // Stock reservation, one guarded UPDATE per (product, size) variant.
        // Each statement is atomic: it only applies when enough stock remains, so
        // concurrent checkouts for the last unit cannot oversell — the loser gets
        // 0 affected rows, restores earlier reservations, and gets 422.
        $byVariant = $items->groupBy(fn ($i) => $i->product_id.':'.($i->size_id ?? 0));
        $reserved = [];
        $failed = null;
        foreach ($byVariant as $lines) {
            $first = $lines->first();
            $qty = $lines->sum('quantity');
            if ($reserve($first->product_id, $first->size_id, $qty)) {
                $reserved[] = [$first->product_id, $first->size_id, $qty];
            } else {
                $failed = $first;
                break;
            }
        }

        if ($failed) {
            foreach ($reserved as [$pid, $sid, $qty]) {
                $restore($pid, $sid, $qty);
            }
            $stock = $failed->size_id
                ? (int) DB::table('product_size')->where('product_id', $failed->product_id)->where('size_id', $failed->size_id)->value('stock')
                : (int) DB::table('products')->where('id', $failed->product_id)->value('product_quantity');

            return response()->json(['message' => "Only {$stock} left of {$failed->product->product_name}."], 422);
        }

        $order = Order::create([
            'user_id' => $request->user()->id,
            'total_price' => $quote['total'],
            'subtotal' => $quote['subtotal'],
            'discount_total' => $quote['discount_total'],
            'voucher_codes' => $quote['vouchers'] ? array_column($quote['vouchers'], 'code') : null,
            'voucher_amount' => $quote['voucher_amount'],
            'tax_amount' => $quote['tax_amount'],
            'shipping_amount' => $quote['shipping_amount'],
            'contact_name' => $data['contact_name'],
            'address' => $data['address'],
            'address_lat' => $data['address_lat'] ?? null,
            'address_lng' => $data['address_lng'] ?? null,
            'status' => 'pending',
            'idempotency_key' => $key,
        ]);

        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->effectivePrice(),
                'color_id' => $item->color_id,
                'size_id' => $item->size_id,
                'size_label' => $item->size?->label,
            ]);
        }

        foreach ($quote['vouchers'] as $applied) {
            \App\Models\Voucher::where('code', $applied['code'])->increment('used_count');
        }

        CartItem::where('user_id', $request->user()->id)->delete();

        $order = $this->settleFreeOrder($order, $request->user()->id);

        return response()->json($order->load('orderItems.product'), 201);
    }
}
