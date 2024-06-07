@extends('globalprofilelayout')
@section('content')
<div>
    <div class="container">
        <h1 class="text-3xl font-bold mb-10">Profile</h1>
        <a href="{{ route('showProfile', ['id' => Auth::id()]) }}" class="bg-black text-white py-2 px-4 rounded-sm hover:bg-gray-900">Back</a>
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

        <form action="{{ route('editProfileHandling', ['id' => Auth::id()]) }}" method="post" class="mt-8">
            @csrf
            <div class="mb-5">
                <label for="name" class="block mb-2">Name</label>
                <input type="name" name="name" id="name" class="w-full p-2 border rounded">
            </div>
            <div class="mb-5">
                <label for="email" class="block mb-2">Email</label>
                <input type="email" name="email" id="email" class="w-full p-2 border rounded">
            </div>
            <div class="mb-5">
                <label for="password" class="block mb-2">Password</label>
                <input type="password" name="password" id="password" class="w-full p-2 border rounded">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Edit Profile</button>
        </form>
    </div>
</div>

@endsection