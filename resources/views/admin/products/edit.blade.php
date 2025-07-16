@extends('layouts.admin')
@section('title', 'Edit Product')
@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-6">Edit Product</h1>
    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="mb-4">
                <label class="block text-sm font-medium">Name</label>
                <input type="text" name="name" class="w-full border rounded p-2 @error('name') border-red-500 @enderror" value="{{ old('name', $product->name) }}">
                @error('name')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium">Price</label>
                <input type="number" name="price" step="0.01" class="w-full border rounded p-2 @error('price') border-red-500 @enderror" value="{{ old('price', $product->price) }}">
                @error('price')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium">Stock</label>
                <input type="number" name="stock" class="w-full border rounded p-2 @error('stock') border-red-500 @enderror" value="{{ old('stock', $product->stock) }}">
                @error('stock')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium">Category</label>
                <select name="category_id" class="w-full border rounded p-2 @error('category_id') border-red-500 @enderror">
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium">Image</label>
                <input type="file" name="image" class="w-full border rounded p-2 @error('image') border-red-500 @enderror">
                @if ($product->image)
                    <img src="{{ asset('storage/products/' . $product->image) }}" alt="{{ $product->name }}" class="w-32 mt-2">
                @endif
                @error('image')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update</button>
        </div>
    </form>
</div>
@endsection