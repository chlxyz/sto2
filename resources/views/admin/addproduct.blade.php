@extends('adminllayout')
@section('content')
<div class="ml-5">
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-5" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @elseif (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-5" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <form action="{{ route('admin.addproduct') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
            <label for="product_photo" class="block text-sm font-medium text-gray-700">Product Photo</label>
            <input type="file" name="product_photo" id="product_photo" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
        </div>
        <div class="mb-4">
            <label for="product_type" class="block text-sm font-medium text-gray-700">Product Type</label>
            <input type="text" name="product_type" id="product_type" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
        </div>
        <div class="mb-4">
            <label for="product_name" class="block text-sm font-medium text-gray-700">Product Name</label>
            <input type="text" name="product_name" id="product_name" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
        </div>
        <div class="mb-4">
            <label for="product_desc" class="block text-sm font-medium text-gray-700">Product Description</label>
            <textarea name="product_desc" id="product_desc" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
        </div>
        <div class="mb-4">
            <label for="product_price" class="block text-sm font-medium text-gray-700">Product Price</label>
            <input type="text" name="product_price" id="product_price" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
        </div>
        <div class="mb-4">
            <label for="product_quantity" class="block text-sm font-medium text-gray-700">Product Quantity</label>
            <input type="text" name="product_quantity" id="product_quantity" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
        </div>
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Add Product
        </button>
    </form>
</div>
@endsection
