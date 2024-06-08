@extends('globallayout')

@section('content')
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

@foreach ($cartItems as $cartItem)
    <div class="flex justify-between items-center border-b-2 border-gray-200 py-2">
        <div class="flex items-center">
            <img src="{{ asset('storage/' . $cartItem->product->product_photo) }}" alt="product photo" class="w-20 h-20 object-cover">
            <div class="ml-4">
                <h1 class="text-xl font-bold">{{ $cartItem->product->product_name }}</h1>
                <p class="text-sm text-gray-600">Price: ${{ $cartItem->product->product_price }}</p>
                <div class="flex items-center mt-2">
                    <button type="button" onclick="decrementQuantity({{ $cartItem->id }})" class="text-blue-500 px-2">-</button>
                    <input type="number" id="quantity_{{ $cartItem->id }}" name="quantity" value="{{ $cartItem->quantity }}" min="1" class="w-12 text-center border border-gray-300 rounded-md mx-2">
                    <button type="button" onclick="incrementQuantity({{ $cartItem->id }})" class="text-blue-500 px-2">+</button>
                </div>
            </div>
        </div>
        <div class="flex items-center">
            <p class="text-sm">Quantity: {{ $cartItem->quantity }}</p>
            <p class="font-bold ml-5">${{ $cartItem->quantity * $cartItem->product->product_price }}</p>
            <form action="{{ route('cart.deleteItem', ['id' => $cartItem->id]) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="ml-4 text-red-600 hover:text-red-800 focus:outline-none">Remove</button>
            </form>
        </div>
    </div>
@endforeach

<div class="mt-5">
    <form action="{{ route('cart.process') }}" method="POST">
        @csrf
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Proceed to Checkout
        </button>
    </form>
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
