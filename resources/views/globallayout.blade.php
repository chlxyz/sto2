<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        .container {
            flex: 1;
        }
    </style>
</head>
<body class="bg-white">
    <nav class="bg-white p-4">
    <div class="container mx-auto flex justify-between items-center">
        <div class="flex items-center">
            <a href="{{ route('dashboard') }}" class="text-black">
            <img src="https://img.icons8.com/?size=100&id=86527&format=png&color=000000" alt="Home Icon" class="inline-block align-middle mr-2 size-8">
            Home</a>
        </div>
        <form action="{{ route('logoutHandler') }}" method="POST" class="flex items-center">
            @csrf
            <a href="{{ route('cart.index') }}" class="text-black block">
                <img src="https://img.icons8.com/?size=100&id=Ot2P5D5MPltM&format=png&color=000000" alt="Shopping Cart Icon" class="inline-block align-middle mr-2 size-8">
                Cart
            </a>
            <a href="{{ route('order.show')}}" class="text-black block ml-5">
                <img src="https://img.icons8.com/?size=100&id=59857&format=png&color=000000" alt="Order Icon" class="inline-block align-middle mr-2 size-8">
                Order
            </a>
            <a href="{{ route('showProfile', ['id' => Auth::id()]) }}" class="text-blue-500 block ml-5">{{ $user->name }}</a>
            <button type="submit" class="border-2 border-solid bg-black text-white border-black px-4 py-2 rounded ml-5">Logout</button>
        </form>
    </div>
    </nav>

    <div class="container mx-auto mt-4">
        @yield('content')
    </div>

    <footer class="bg-gray-800 p-4 text-white text-center">
        <div class="container mx-auto">
            All rights reserved
        </div>
    </footer>
</body>
</html>
