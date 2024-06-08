<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;


class CartController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $cartItems = CartItem::where('user_id', auth()->id())->get();
    
        if ($cartItems->isEmpty()) {
            return view('global.cart', compact('cartItems', 'user'))->with('error', 'Cart is empty');
        } else {
            return view('global.cart', compact('cartItems', 'user'))->with('success', 'Cart is filled');
        }
    }
    

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $existingCartItem = CartItem::where('user_id', auth()->id())
                                     ->where('product_id', $request->product_id)
                                     ->first();

        if ($existingCartItem) {
            $existingCartItem->increment('quantity');
        } else {
            CartItem::create([
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Item added to cart successfully');
    }

    public function deleteItem($id)
    {
        $cartItem = CartItem::find($id);
        $cartItem->delete();
        
        return redirect()->route('cart.index')->with('success', 'Item removed from cart successfully');
    }

    public function processOrder(Request $request)
    {
        $user = Auth::user();
        $cartItems = CartItem::where('user_id', auth()->id())->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Cart is empty');
        }

        $totalPrice = 0;
        foreach ($cartItems as $cartItem) {
            $totalPrice += $cartItem->product->product_price * $cartItem->quantity;
        }

        $order = Order::create([
            'user_id' => auth()->id(),
            'total_price' => $totalPrice,
            'status' => 'pending'
        ]);

        foreach ($cartItems as $cartItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->product->product_price
            ]);
        }

        CartItem::where('user_id', auth()->id())->delete();

        return redirect()->route('payments.methods', $order->id)->with('success', 'Order processed successfully, please choose a payment method');
    }

}
