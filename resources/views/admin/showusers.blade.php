@extends('adminllayout')
@section('content')
<style>
    .user-table {
    border-collapse: separate;
    border-spacing: 50px; /* Adjust this value to set the desired gap between columns */
}

.user-table th,
.user-table td {
    padding: 10px; /* Adjust this value to set the padding inside cells */
}
</style>
<div class="container mx-auto mt-[-5%] ml-2">
    @foreach ($users as $user)
        <div class="flex justify-between items-center border-b-2 border-gray-200 py-2">
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->created_at }}</td>
                    <td>{{ $user->updated_at }}</td>
                    <td>
                        <form action="{{ route('admin.deleteuser', ['id' => $user->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500">Delete</button>
                        </form>
                    </td>
                </tr>
                <!-- Repeat the above <tr> block for each user -->
            </tbody>
        </table>

        </div>
    @endforeach
</div>
@endsection
