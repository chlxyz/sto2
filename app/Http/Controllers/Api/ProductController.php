<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    use PresentsProducts;

    public function index()
    {
        $products = Product::with(['types', 'images', 'colors', 'sizes', 'sizeChart'])
            ->where('is_listed', true)
            ->orderBy('position')
            ->latest()
            ->get();

        return response()->json($products->map(fn ($p) => self::presentProduct($p)));
    }

    public function types()
    {
        return response()->json(\App\Models\Type::orderBy('name')->pluck('name'));
    }

    public function sizes()
    {
        return response()->json(\App\Models\Size::orderBy('label')->pluck('label'));
    }

    public function colors()
    {
        return response()->json(\App\Models\Color::orderBy('name')->get(['name', 'hex']));
    }
}
