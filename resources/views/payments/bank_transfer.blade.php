@extends('globallayout')

@section('content')
<div class="container">
    <h1>Bank Transfer Details for Order #{{ $order->id }}</h1>
    <p>Please transfer the amount to the following bank account:</p>
    <p>Bank: XYZ Bank</p>
    <p>Account Number: 123456789</p>
    <p>Amount: ${{ $order->total_price }}</p>
    <form action="{{ route('payments.confirm_bank_transfer', $order->id) }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="btn btn-success">OK</button>
    </form>
</div>
@endsection
