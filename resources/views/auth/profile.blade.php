@extends('globallayout')
@section('content')
<div>
    <div class="container">
        <h1 class="text-3xl font-bold mb-10">Profile</h1>
        <div class="mb-5">
            <label for="name" class="block mb-2">Name</label>
            <input type="name" name="name" id="name" class="w-full p-2 border rounded" value="{{ $user->name }}" disabled>
        </div>
        <div class="mb-5">
            <label for="email" class="block mb-2">Email</label>
            <input type="email" name="email" id="email" class="w-full p-2 border rounded" value="{{ $user->email }}" disabled>
        </div>
        <a href="{{ route('editProfileForm', ['id' => Auth::id()]) }}" class="text-blue-500 mt-5 block">Edit Profile</a>
        <form action="{{ route('deleteHandler', ['id'=>Auth::id()]) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-blue-500 mt-5 block">Delete Profile</button>
        </form>
    </div>
</div>

@endsection