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
            <form action="{{ route('payments.scanbank', ['order' => $order->id]) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Pay with ScanBank
                </button>
            </form>
            <form action="{{ route('payments.bank_transfer', ['order' => $order->id]) }}" method="POST" class="inline-block ml-5">
                @csrf
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Pay with Bank Transfer
                </button>
            </form>
            <form action="{{ route('payments.cod', ['order' => $order->id]) }}" method="POST" class="inline-block ml-5">
                @csrf
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Cash on Delivery (COD)
                </button>
            </form>
        </div>
    </div>
@endsection
