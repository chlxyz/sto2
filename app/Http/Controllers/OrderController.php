<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function showOrders()
    {
        $user = Auth::user();
        $orders = Order::where('user_id', auth()->id())->get();
    
        if ($orders->isEmpty()) {
            return view('global.order', compact('orders', 'user'))->with('error', 'Cart is empty');
        } else {
            return view('global.order', compact('orders', 'user'))->with('success', 'Cart is filled');
        }
    }

public function showAdminOrders()
    {
        $user = Auth::user();
        // Was scoped to auth()->id(), so /adminorders showed the admin their OWN
        // orders and nothing else. Route is now auth+admin; show every order.
        $orders = Order::with(['user', 'orderItems.product'])->latest()->get();
    
        if ($orders->isEmpty()){
            return view('admin.order', compact('orders', 'user'))->with('error', 'No orders yet');
        } else {
            return view('admin.order', compact('orders', 'user'))->with('success', 'Order available');
        }
    }

    public function cancelOrders($orderId){
        // Scoped + status guard, matching Api\OrderController::destroy.
        $order = Order::where('user_id', auth()->id())->findOrFail($orderId);

        abort_if(in_array($order->status, ['cancelled', 'completed'], true), 422, 'Order cannot be cancelled at this stage.');

        $order->delete();

        return redirect()->route('order.show');
    }

}
