@extends('layouts.admin')
@section('title', 'Users')
@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-6">Users</h1>
    <table class="w-full bg-white shadow rounded">
        <thead>
            <tr class="bg-gray-100">
                <th class="p-3 text-left">Name</th>
                <th class="p-3 text-left">Email</th>
                <th class="p-3 text-left">Role</th>
                <th class="p-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td class="p-3">{{ $user->name }}</td>
                    <td class="p-3">{{ $user->email }}</td>
                    <td class="p-3">{{ $user->role }}</td>
                    <td class="p-3">
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="p-3 text-center">No users found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection