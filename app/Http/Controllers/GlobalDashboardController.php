<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class GlobalDashboardController extends Controller
{
    public function index(){
        $user = Auth::user();
        $products = Product::all();

        if($products->isEmpty()){
            return view ('global.showproduct',  compact('products', 'user'))->with('error', 'No product available');
        }
        else {
            return view ('global.showproduct', compact('products', 'user'))->with('success', 'Product available');
        }
    }
}
