<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public const METHODS = ['ScanBank', 'Bank Transfer', 'Cash on Delivery'];

    private function scoped(Request $request, $orderId): Order
    {
        return Order::where('user_id', $request->user()->id)->findOrFail($orderId);
    }

    public function show(Request $request, $orderId)
    {
        $order = $this->scoped($request, $orderId)->load('orderItems.product');

        return response()->json(['order' => $order, 'methods' => self::METHODS]);
    }

    public function pay(Request $request, $orderId)
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(self::METHODS)],
            'idempotency_key' => 'required|string',
        ]);

        $order = $this->scoped($request, $orderId);

        if ($data['idempotency_key'] !== $order->idempotency_key) {
            return response()->json(['message' => 'Cart changed since checkout. Please checkout again.'], 422);
        }

        // Replay: same key on a paid order returns the result without re-processing.
        if ($order->is_paid) {
            return response()->json($order->load('orderItems.product'), 200);
        }

        // ScanBank is a two-step flow: issue a scan token first, paid on scan.
        if ($data['payment_method'] === 'ScanBank') {
            $order->update([
                'payment_method' => 'ScanBank',
                'scan_token' => $order->scan_token ?? Str::random(32),
            ]);

            return response()->json([
                'status' => 'awaiting_scan',
                'scan_token' => $order->scan_token,
                'qr_payload' => 'sto2pay://scan/'.$order->scan_token,
                'order' => $order->load('orderItems.product'),
            ], 202);
        }

        $order->update([
            'payment_method' => $data['payment_method'],
            'is_paid' => true,
            'status' => 'paid',
        ]);

        Activity::create([
            'user_id' => $request->user()->id,
            'order_id' => $order->id,
            'activity_type' => 'payment',
            'details' => 'Paid with '.$data['payment_method'],
        ]);

        return response()->json($order->load('orderItems.product'), 200);
    }

    // Scan confirmation: idempotent — replays and stale tokens are safe.
    public function scan(Request $request, $orderId)
    {
        $data = $request->validate(['scan_token' => 'required|string']);

        $order = $this->scoped($request, $orderId);

        if (! $order->scan_token || $data['scan_token'] !== $order->scan_token) {
            return response()->json(['message' => 'Invalid or expired scan code. Generate a new one.'], 422);
        }

        if ($order->is_paid) {
            return response()->json($order->load('orderItems.product'), 200);
        }

        // ponytail: no DB::transaction — pooled pgsql aborts multi-statement transactions.
        // Status jumps straight to prepared (fulfillment flow starts at payment).
        $order->update(['is_paid' => true, 'status' => 'prepared']);

        Activity::create([
            'user_id' => $request->user()->id,
            'order_id' => $order->id,
            'activity_type' => 'payment',
            'details' => 'Paid with ScanBank (scan)',
        ]);

        return response()->json($order->load('orderItems.product'), 200);
    }
}
