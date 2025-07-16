@extends('layouts.admin')
@section('title', 'Products')
@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Products</h1>
        <a href="{{ route('admin.products.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded">Add Product</a>
    </div>
    <table class="w-full bg-white shadow rounded">
        <thead>
            <tr class="bg-gray-100">
                <th class="p-3 text-left">Name</th>
                <th class="p-3 text-left">Price</th>
                <th class="p-3 text-left">Stock</th>
                <th class="p-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td class="p-3">{{ $product->name }}</td>
                    <td class="p-3">${{ number_format($product->price, 2) }}</td>
                    <td class="p-3">{{ $product->stock }}</td>
                    <td class="p-3">
                        <a href="{{ route('admin.products.show', $product) }}" class="text-blue-500">View</a>
                        <a href="{{ route('admin.products.edit', $product) }}" class="text-green-500 ml-2">Edit</a>
                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 ml-2" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="p-3 text-center">No products found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection