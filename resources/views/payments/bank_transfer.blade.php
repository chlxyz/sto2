@extends('globallayout')

@section('content')
<div class="container">
    <h1>Bank Transfer Details for Order #{{ $order->id }}</h1>
    <p>Please transfer the amount to the following bank account:</p>
    <p>Bank: XYZ Bank</p>
    <p>Account Number: 123456789</p>
    <p>Amount: ${{ $order->total_price }}</p>
    <a href="{{ route('payments.confirm_bank_transfer', $order->id) }}" class="btn btn-success">OK</a>
</div>
@endsection
