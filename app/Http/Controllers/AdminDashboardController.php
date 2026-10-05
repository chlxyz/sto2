<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function index(Request $request){
        $products = Product::all();

        if($products->isEmpty()){
            return view ('admin.showproduct', compact('products'))->with('error', 'No product available');
        }
        else {
            return view ('admin.showproduct', compact('products'))->with('success', 'Product available');
        }
    }

    public function user_index(Request $request){
        $users = User::all();

        if($users->isEmpty()){
            return view ('admin.showusers', compact('users'))->with('error', 'No product available');
        }
        else {
            return view ('admin.showusers', compact('users'))->with('success', 'Product available');
        }
    }
    
    public function findProduct(Request $request){
        $product = Product::findOrFail($request->product_id);

        return $product;
    }

    public function addProductForm(){
        return view ('admin.addProduct');
    }

    public function addProduct(Request $request){
        $request->validate([
            'product_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'product_name' => 'required',
            'product_desc' => 'required',
            'product_quantity' => 'required|integer',
            'product_price' => 'required|numeric',
            'product_type' => 'required',     
        ]);
    
        $productExists = Product::where('product_name', $request->product_name)->exists();
        
        if($productExists){
            return redirect()->route('add.productform')->with('error', 'Product already exists! Instead, update the quantity of the existing product.');
        }

        $productPhotoPath = $request->file('product_photo')->store('product_photos', 'public');

        $product = new Product();
        $product->product_photo = $productPhotoPath;
        $product->product_name = $request->product_name;
        $product->product_desc = $request->product_desc;
        $product->product_quantity = $request->product_quantity;
        $product->product_price = $request->product_price;
        $product->product_type = $request->product_type;
    
        if($product->save()){
            return redirect()->route('admin.dashboard')->with('success', 'Product added successfully');
        } else {
            return redirect()->route('add.productform')->with('error', 'Failed to add product');
        }
    }
    
    public function deleteProduct($id){
        $product = Product::findOrFail($id);

        // Mirrors Api\AdminController::destroyProduct so the two admin paths
        // can't diverge into different integrity rules.
        if (\App\Models\OrderItem::where('product_id', $product->id)->exists()) {
            return redirect()->route('admin.dashboard')->with('error', 'Product is referenced by existing orders. Unlist it instead.');
        }

        $product->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Product deleted');
    }

    public function deleteUser($id){
        $user = User::findOrFail($id);

        // Mirrors Api\AdminController::destroyUser.
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.allusers')->with('error', 'You cannot delete your own account here.');
        }

        if ($user->isAdmin() && User::where('email', config('admin.email'))->count() <= 1) {
            return redirect()->route('admin.allusers')->with('error', 'Cannot delete the last admin account.');
        }

        $open = \App\Models\Order::where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'completed', 'refunded'])
            ->count();

        if ($open > 0) {
            return redirect()->route('admin.allusers')->with('error', "User has {$open} open order(s). Close them first.");
        }

        $user->delete();

        return redirect()->route('admin.allusers')->with('success', 'User deleted');
    }


    public function editProductForm($id){
        $product = Product::findOrFail($id);

        return view ('admin.edit', compact('product'));
    }

public function editProductHandler(Request $request, $id) {
        $request->validate([
            'product_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'product_name' => 'required|string|max:255',
            'product_desc' => 'required|string',
            'product_quantity' => 'required|integer|min:0',
            'product_price' => 'required|numeric|min:0',
            'product_type' => 'required',   
        ]);
    
        $product = Product::findOrFail($id);

        // product_photo is a file input. It used to be read as a plain string and
        // mass-assigned, so any caller could rewrite the column to an arbitrary
        // string that destroyProduct would then delete from the public disk.
        $data = $request->only('product_name', 'product_desc', 'product_quantity', 'product_price', 'product_type');

        if ($request->hasFile('product_photo')) {
            $data['product_photo'] = $request->file('product_photo')->store('product_photos', 'public');
        }
    
        $product->update($data);

        if($product){
            return redirect()->route('edit.productform', ['id'=>$id])->with('success', 'Successfully updated');
        } else {
            return redirect()->route('edit.productform', ['id'=>$id])->with('error', 'Failed to update');
        }
    }
}
