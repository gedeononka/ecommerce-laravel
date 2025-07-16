@extends('layouts.admin')
@section('title', $product->name)
@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-6">{{ $product->name }}</h1>
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="mb-4">
            <h2 class="text-lg font-semibold">Price</h2>
            <p>${{ number_format($product->price, 2) }}</p>
        </div>
        <div class="mb-4">
            <h2 class="text-lg font-semibold">Stock</h2>
            <p>{{ $product->stock }}</p>
        </div>
        <div class="mb-4">
            <h2 class="text-lg font-semibold">Category</h2>
            <p>{{ $product->category->name }}</p>
        </div>
        @if ($product->image)
            <div class="mb-4">
                <h2 class="text-lg font-semibold">Image</h2>
                <img src="{{ asset('storage/products/' . $product->image) }}" alt="{{ $product->name }}" class="w-64">
            </div>
        @endif
        <div class="flex space-x-2">
            <a href="{{ route('admin.products.edit', $product) }}" class="bg-blue-500 text-white px-4 py-2 rounded">Edit</a>
            <form action="{{ route('admin.products.destroy', $product) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded" onclick="return confirm('Are you sure?')">Delete</button>
            </form>
        </div>
    </div>
</div>
@endsection