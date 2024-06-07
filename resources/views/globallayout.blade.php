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
<body class="bg-gray-100">
    <nav class="bg-gray-800 p-4">
    <div class="container mx-auto flex justify-between items-center">
        <div class="flex items-center">
            <a href="{{ route('dashboard') }}" class="text-white">Home</a>
        </div>
        <form action="{{ route('logoutHandler') }}" method="POST" class="flex items-center"> <!-- Updated here -->
            @csrf
            <a href="" class="text-white block">Cart</a>
            <a href="" class="text-white block ml-5">Order</a>
            <a href="{{ route('showProfile', ['id' => Auth::id()]) }}" class="text-blue-500 block ml-5">{{ $user->name }}</a> <!-- Moved inside form and added margin -->
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
