<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function index(Request $request){
        $products = Product::all();

        if($products->isEmpty()){
            return view ('admin.showproduct')->with('error', 'No product available');
        }
        else {
            return view ('admin.showproduct', compact('products'))->with('success', 'Product available');
        }
    }
    public function findProduct(Request $request){
        $product = Product::find($request->product_id);
    
        return $product;
    }

    public function addProductForm(){
        return view ('admin.addProduct');
    }

    public function addProduct(Request $request){
        $request->validate([
            'product_photo' => 'required',
            'product_name' => 'required',
            'product_desc' => 'required',
            'product_quantity' => 'required',
            'product_price' => 'required',
            'product_type' => 'required',     
        ]);
    
        // Check if the product already exists
        $productExists = Product::where('product_name', $request->product_name)->exists();
        
        if($productExists){
            // If product already exists, redirect with error message
            return redirect()->route('admin.dashboard')->with('error', 'Product already exists! Instead, update the quantity of the existing product.');
        }
        
        // Create a new product instance
        $product = new Product();
        $product->product_photo = $request->product_photo;
        $product->product_name = $request->product_name;
        $product->product_desc = $request->product_desc;
        $product->product_quantity = $request->product_quantity;
        $product->product_price = $request->product_price;
        $product->product_type = $request->product_type;
    
        // Save the product
        if($product->save()){
            // Redirect with success message if saved successfully
            return redirect()->route('add.productform')->with('success', 'Product added successfully');
        } else {
            // Redirect with error message if failed to save
            return redirect()->route('add.productform')->with('error', 'Failed to add product');
        }
    }

    public function deleteProduct($id){
        $product = Product::find($id);
        $product->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Product deleted');
    }


    public function editProductForm($id){
        $product = Product::findOrFail($id);

        return view ('admin.edit', compact('product'));
    }

    public function editProductHandler(Request $request, $id) {
        $request->validate([
            'product_photo' => 'required',
            'product_name' => 'required',
            'product_desc' => 'required',
            'product_quantity' => 'required',
            'product_price' => 'required',
            'product_type' => 'required',   
        ]);
    
        $product = Product::findOrFail($id);
    
        $data = $request->only('product_photo', 'product_name', 'product_desc', 'product_quantity', 'product_price', 'product_type');

    
        $product->update($data);

        if($product){
            return redirect()->route('edit.productform', ['id'=>$id])->with('success', 'Successfully updated');
        }else {
            return redirect()->route('edit.productform', ['id'=>$id])->with('error', 'Failed to update');
        }
    }
}
