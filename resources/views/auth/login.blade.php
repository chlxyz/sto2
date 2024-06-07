@extends('authlayout')
@section('content')
<div class="flex justify-center items-center h-screen bg-gray-200">
    <div class="p-10 bg-white rounded shadow w-96">
        <h1 class="text-3xl font-bold mb-10">Login</h1>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-5" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ route('loginHandler') }}" method="post">
            @csrf
            <div class="mb-5">
                <label for="email" class="block mb-2">Email</label>
                <input type="email" name="email" id="email" class="w-full p-2 border rounded">
            </div>
            <div class="mb-5">
                <label for="password" class="block mb-2">Password</label>
                <input type="password" name="password" id="password" class="w-full p-2 border rounded">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Login</button>
        </form>
        <a href="{{ route('registerForm') }}" class="text-blue-500 mt-5 block">Don't have an account? Register</a>
    </div>
</div>
@endsection
