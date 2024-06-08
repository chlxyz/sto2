@extends('globallayout')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-5">Select Payment Method</h1>

        <p>Order ID: {{ $order->id }}</p>
        <p>Order Total: ${{ $order->total_price }}</p>

        <div class="mt-5">
            <h2 class="text-lg font-semibold mb-3">Order Details:</h2>
            <ul>
                @foreach($order->orderItems as $item)
                    <li>
                        <p>Product Name: {{ $item->product->product_name }}</p>
                        <p>Price: {{ $item->product->product_price }}</p>
                        <p>Quantity: {{ $item->quantity }}</p>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-5">
            <a href="{{ route('payments.scanbank', ['order' => $order->id]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Pay with ScanBank
            </a>
            <a href="{{ route('payments.bank_transfer', ['order' => $order->id]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded ml-5">
                Pay with Bank Transfer
            </a>
            <a href="{{ route('payments.cod', ['order' => $order->id]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded ml-5">
                Cash on Delivery (COD)
            </a>
        </div>
    </div>
@endsection
