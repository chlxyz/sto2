@extends('globallayout')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-5">Order Details</h1>

        @if ($orders->isEmpty())
            <p>No orders found for this user.</p>
        @else
            @foreach($orders as $order)
                <div class="bg-white shadow-md rounded-md p-6 mb-6">
                    <p class="text-lg font-semibold">Order ID: <span class="text-gray-700">{{ $order->id }}</span></p>
                    <p class="text-lg font-semibold">Total Price: <span class="text-gray-700">${{ $order->total_price }}</span></p>
                    <p class="text-lg font-semibold">Status: <span class="text-gray-700">{{ $order->status }}</span></p>
                    <p class="text-lg font-semibold">Items:</p>
                    <div class="max-h-60 overflow-y-auto">
                        <ul class="list-disc pl-6 mt-2">
                            @foreach($order->orderItems as $item)
                                <li class="text-gray-800">{{ $item->product->product_name }} - Quantity: {{ $item->quantity }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @if (!$order->is_paid = 0)
                        <div class="mt-5">
                            <a href="{{ route('payments.methods', ['order' => $order->id]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Pay Now
                            </a>
                        </div>
                    @endif
                    <form action="{{ route('order.cancel', ['order' => $order->id]) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mt-5">Cancel</button>
                    </form>
                </div>
            @endforeach
        @endif
    </div>
@endsection
