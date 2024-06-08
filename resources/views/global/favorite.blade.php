@extends('globallayout')
@section('content')
<div class="container mx-auto mt-4 ml-2">
    @foreach ($products as $product)
        <div class="flex justify-between items-center border-b-2 border-gray-200 py-2">
            <div>
                <img src="{{ asset('storage/' . $product->product_photo) }}" alt="product photo" class="w-20 h-20 object-cover">
            </div>
            <div>
                <h1 class="text-xl font-bold">{{ $product->product_name }}</h1>
                <p class="text-sm">{{ $product->product_desc }}</p>
            </div>
            <div>
                <p class="text-sm">Price: ${{ $product->product_price }}</p>
            </div>
            <div>
                <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500">Delete</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
