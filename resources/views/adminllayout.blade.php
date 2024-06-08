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
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('showproduct-link').addEventListener('click', function (event) {
            event.preventDefault();
            loadContent('{{ route('admin.dashboard') }}');
        });

        document.getElementById('addproduct-link').addEventListener('click', function (event) {
            event.preventDefault();
            loadContent('{{ route('add.productform') }}');
        });

        document.getElementById('showorders-link').addEventListener('click', function (event) {
            event.preventDefault();
            loadContent('{{ route('order.adminshow') }}');
        });

        document.getElementById('allusers-link').addEventListener('click', function (event) {
            event.preventDefault();
            loadContent('{{ route('admin.allusers') }}');
        });
    });

    function loadContent(url) {
        fetch(url)
            .then(response => response.text())
            .then(data => {
                document.getElementById('main-content').innerHTML = data;
            })
            .catch(error => console.error('Error loading content:', error));
    }
</script>
</head>
<body class="bg-gray-100">
    <nav class="bg-gray-800 p-4">
    <div class="container mx-auto flex justify-between items-center">
        <div class="flex items-center">
            <a href="{{ route('dashboard') }}" class="text-white">Global Dashboard</a>
            <a href="{{ route('admin.dashboard') }}" class="text-white ml-5">Home</a>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.dashboard') }}" class="text-white">Admin Panel</a>
        </div>
        <form action="{{ route('logoutHandler') }}" method="POST" class="flex items-center text-white">
            @csrf
            <button type="submit" class="">Logout</button>
        </form>
    </div>
    </nav>

    <div class="container mx-auto mt-4 flex">
        <nav class="w-1/4 bg-gray-200 p-4">
            <div>
                <button onclick="location.href='{{ route('admin.dashboard') }}'" class="block py-2 px-4 text-gray-600 hover:bg-gray-300">Products</button>
                <button onclick="location.href='{{ route('add.productform') }}'" class="block py-2 px-4 text-gray-600 hover:bg-gray-300">Add Product</button>
                <button onclick="location.href='{{ route('order.adminshow') }}'" class="block py-2 px-4 text-gray-600 hover:bg-gray-300">Orders</button>
                <a href="#" class="block py-2 px-4 text-gray-600 hover:bg-gray-300">Payment Methods</a>
                <button onclick="location.href='{{ route('admin.allusers') }}'" class="block py-2 px-4 text-gray-600 hover:bg-gray-300">Manage Users</button>
            </div>
        </nav>

        <div class="w-3/4">
            @yield('content')
        </div>
    </div>

    <footer class="bg-gray-800 p-4 text-white text-center">
        <div class="container mx-auto">
            All rights reserved
        </div>
    </footer>
</body>
</html>
