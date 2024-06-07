@extends('globallayout')
@section('content')
<div class="container mx-auto mt-4">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($products as $product)
            <div class="bg-white shadow-md rounded-md overflow-hidden">
                <img src="{{ asset('storage/' . $product->product_photo) }}" alt="product photo" class="w-full h-64 object-cover">
                <div class="p-4">
                    <h1 class="text-xl font-bold mb-2">{{ $product->product_name }}</h1>
                    <p class="text-sm text-gray-600 mb-2">{{ $product->product_desc }}</p>
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-700">Price: ${{ $product->product_price }}</p>
                        <div class="flex items-center">
                            <button type="button" onclick="decrementQuantity({{ $product->id }})" class="text-blue-500 px-2">-</button>
                            <input type="number" id="quantity_{{ $product->id }}" value="1" class="w-12 text-center border border-gray-300 rounded-md mx-2">
                            <button type="button" onclick="incrementQuantity({{ $product->id }})" class="text-blue-500 px-2">+</button>
                        </div>
                    </div>
                    <div class="mt-4">
                        <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?')">
                            @csrf
                            <button type="submit" class="bg-green-500 text-black rounded-xl pl-5 pr-5">Order now</button>
                            <button type="submit" class="bg-green-500 text-black rounded-xl pl-5 pr-5">Add to cart</button>
                            <button type="submit" class="bg-green-500 text-black rounded-xl pl-5 pr-5">Favorite</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    function incrementQuantity(productId) {
        var input = document.getElementById('quantity_' + productId);
        input.value = parseInt(input.value) + 1;
    }

    function decrementQuantity(productId) {
        var input = document.getElementById('quantity_' + productId);
        if (parseInt(input.value) > 1) {
            input.value = parseInt(input.value) - 1;
        }
    }
</script>
@endsection
