@extends('globallayout')
@section('content')
<div class="container mx-auto mt-4">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-5" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @elseif (session()->has('error'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-5" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @foreach ($products as $product)
            <div class="bg-white shadow-md rounded-md overflow-hidden">
                <img src="{{ asset('storage/' . $product->product_photo) }}" alt="product photo" class="w-full h-64 object-cover">
                <div class="p-4">
                    <h1 class="text-xl font-bold mb-2">{{ $product->product_name }}</h1>
                    <p class="text-sm text-gray-600 mb-2">{{ $product->product_desc }}</p>
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-700">Price: ${{ $product->product_price }}</p>
                    </div>
                    <div class="mt-4">
                        <form id="cartForm_{{ $product->id}}" action="{{ route('cart.add') }}" method="POST">
                            @csrf
                            <button type="button" onclick="addToCart({{ $product->id }})" class="bg-green-500 text-black rounded-xl pl-5 pr-5 mt-2">Order Now!</button>
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div class="flex items-center">
                                <button type="button" onclick="decrementQuantity({{ $product->id }})" class="text-blue-500 px-2">-</button>
                                <input type="number" id="quantity_{{ $product->id }}" name="quantity" value="1" min="1" class="w-12 text-center border border-gray-300 rounded-md mx-2">
                                <button type="button" onclick="incrementQuantity({{ $product->id }})" class="text-blue-500 px-2">+</button>
                            </div>
                            <button type="button" onclick="addToCart({{ $product->id }})" class="bg-green-500 text-black rounded-xl pl-5 pr-5 mt-2">Add to cart</button>
                        </form>
                        <button type="submit" class="bg-green-500 text-black rounded-xl pl-5 pr-5">Favorite</button>
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

    function addToCart(productId) {
        var form = document.getElementById('cartForm_' + productId);
        var input = document.getElementById('quantity_' + productId);
        form.querySelector('[name="quantity"]').value = input.value;
        form.submit();
    }
</script>
@endsection
