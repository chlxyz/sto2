<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function showPaymentMethods($orderId)
    {
        $user = Auth::user();
        $order = Order::where('user_id', auth()->id())->where('id', $orderId)->firstOrFail();
        return view('payments.methods', compact('order', 'user'));
    }

    public function processPayment(Request $request, $orderId)
    {
        // Validate the request data as needed

        // Retrieve the order
        $order = Order::where('user_id', auth()->id())->where('id', $orderId)->firstOrFail();

        // Update the order status or perform any necessary actions based on the payment method
        if ($request->payment_method === 'ScanBank') {
            return $this->payWithScanBank($orderId);
        } elseif ($request->payment_method === 'Bank Transfer') {
            return $this->payWithBankTransfer($orderId);
        } elseif ($request->payment_method === 'Cash on Delivery') {
            return $this->payWithCOD($orderId);
        }

        // For example, if payment method is "ScanBank", update the payment method and set is_paid to true
        // Similarly, handle other payment methods as required

        // Assuming the payment is successful, update the order status and log the payment activity
        $order->update([
            'is_paid' => true,
            // Also update other fields as necessary, such as payment_method
        ]);

        // Log the payment activity
        Activity::create([
            'user_id' => auth()->id(),
            'order_id' => $order->id,
            'activity_type' => 'payment',
            'details' => 'Payment processed successfully'
        ]);

        // Redirect back to the order details page with a success message
        return redirect()->route('order.show', $orderId)->with('success', 'Payment processed successfully');
    }


    private function updateOrderStatus($orderId)
    {
        $order = Order::findOrFail($orderId);
        $order->update(['is_paid' => true]);

        // Log the payment activity if needed
        Activity::create([
            'user_id' => auth()->id(),
            'order_id' => $order->id,
            'activity_type' => 'payment',
            'details' => 'Payment processed successfully'
        ]);

        // Redirect back to the order details page with a success message
        return redirect()->route('order.show', $orderId)->with('success', 'Payment processed successfully');
    }

    public function payWithScanBank($orderId)
    {
        $order = Order::where('user_id', auth()->id())->where('id', $orderId)->firstOrFail();
        $order->update(['payment_method' => 'ScanBank', 'is_paid' => 1, 'status' => 'paid']); // Update status to 'paid'
    
        Activity::create([
            'user_id' => auth()->id(),
            'order_id' => $order->id,
            'activity_type' => 'payment',
            'details' => 'Paid with ScanBank'
        ]);
    
        return redirect()->route('order.show', $orderId)->with('success', 'Payment successful via ScanBank');
    }

    public function payWithBankTransferUI($orderId){
        $user = Auth::user();
        $order = Order::where('user_id', auth()->id())->where('id', $orderId)->firstOrFail();
        return view('payments.bank_transfer', compact('order', 'user'));
    }


    
    public function payWithBankTransfer($orderId)
    {
        $order = Order::where('user_id', auth()->id())->where('id', $orderId)->firstOrFail();
        $order->update(['payment_method' => 'Bank Transfer', 'is_paid' => 1, 'status' => 'paid']); // Update status to 'paid'
    
        Activity::create([
            'user_id' => auth()->id(),
            'order_id' => $order->id,
            'activity_type' => 'payment',
            'details' => 'Paid with Bank Transfer'
        ]);
    
        return redirect()->route('payments.bank_transfer_terminal', $orderId)->with('success', 'Payment successful via Bank Transfer');
    }

    public function confirmBankTransferPayment($orderId)
    {
        $order = Order::where('user_id', auth()->id())->where('id', $orderId)->firstOrFail();
        $order->update(['payment_method' => 'Bank Transfer', 'is_paid' => true]);

        Activity::create([
            'user_id' => auth()->id(),
            'order_id' => $order->id,
            'activity_type' => 'payment',
            'details' => 'Paid with Bank Transfer'
        ]);

        return redirect()->route('order.show', $orderId)->with('success', 'Payment successful via Bank Transfer');
    }
    
    public function payWithCOD($orderId)
    {
        $order = Order::where('user_id', auth()->id())->where('id', $orderId)->firstOrFail();
        $order->update(['payment_method' => 'Cash on Delivery', 'is_paid' => 1, 'status' => 'paid']); // Update status to 'paid'
    
        Activity::create([
            'user_id' => auth()->id(),
            'order_id' => $order->id,
            'activity_type' => 'payment',
            'details' => 'Paid with Cash on Delivery'
        ]);
    
        return redirect()->route('order.show', $orderId)->with('success', 'Payment successful via Cash on Delivery');
    }
    


    public function confirmCODPayment($orderId)
    {
        $order = Order::where('user_id', auth()->id())->where('id', $orderId)->firstOrFail();
        $order->update(['is_paid' => true]);

        Activity::create([
            'user_id' => auth()->id(),
            'order_id' => $order->id,
            'activity_type' => 'payment',
            'details' => 'Paid with Cash on Delivery'
        ]);

        return redirect()->route('order.show', $orderId)->with('success', 'Payment confirmed for Cash on Delivery');
    }
}
