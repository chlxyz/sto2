<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;

class FavoriteController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $products = $user->products();
    
        if ($products->isEmpty()) {
            return view('global.favorite', compact('products', 'user'))->with('error', 'No products available');
        } else {
            return view('global.favorite', compact('products', 'user'))->with('success', 'Products available');
        }
    }
    public function addToFavorite($id){
        $user = Auth::user();
        $product = Product::find($id);

        $user->products()->attach($product);

        return redirect()->route('favorite.index')->with('success', 'Item added to favorite successfully');
    }
}
