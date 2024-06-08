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
        $orders = Order::where('user_id', auth()->id())->get();
    
        if ($orders->isEmpty()) {
            return view('admin.order', compact('orders', 'user'))->with('error', 'Cart is empty');
        } else {
            return view('admin.order', compact('orders', 'user'))->with('success', 'Cart is filled');
        }
    }


    public function cancelOrders($orderId){
        $orders = Order::find($orderId);
        $orders->delete();

        return redirect()->route('order.show');
    }

}
