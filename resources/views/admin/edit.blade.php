@extends('adminllayout')
@section('content')
<div>
    <div class="container">
        <h1 class="text-3xl font-bold mb-10">Edit Product</h1>
        <a href="{{ route('admin.dashboard') }}" class="bg-black text-white py-2 px-4 rounded-sm hover:bg-gray-900">Back</a>
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-5 mt-8" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @elseif (session()->has('error'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-5 mt-8" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ route('admin.editproduct', ['id' => $product->id]) }}" method="post" class="mt-8">
            @csrf
            <div class="mb-5">
                <label for="product_photo" class="block mb-2">Product Photo</label>
                <input type="file" name="product_photo" id="product_photo" class="w-full p-2 border rounded">
            </div>
            <div class="mb-5">
                <label for="product_name" class="block mb-2">Product Name</label>
                <input type="text" name="product_name" id="product_name" class="w-full p-2 border rounded" value="{{ $product->product_name }}">
            </div>
            <div class="mb-5">
                <label for="product_desc" class="block mb-2">Product Description</label>
                <textarea name="product_desc" id="product_desc" rows="3" class="w-full p-2 border rounded">{{ $product->product_desc }}</textarea>
            </div>
            <div class="mb-5">
                <label for="product_quantity" class="block mb-2">Product Quantity</label>
                <input type="text" name="product_quantity" id="product_quantity" class="w-full p-2 border rounded" value="{{ $product->product_quantity }}">
            </div>
            <div class="mb-5">
                <label for="product_price" class="block mb-2">Product Price</label>
                <input type="text" name="product_price" id="product_price" class="w-full p-2 border rounded" value="{{ $product->product_price }}">
            </div>
            <div class="mb-5">
                <label for="product_type" class="block mb-2">Product Type</label>
                <input type="text" name="product_type" id="product_type" class="w-full p-2 border rounded" value="{{ $product->product_type }}">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Edit Product</button>
        </form>
    </div>
</div>
@endsection
