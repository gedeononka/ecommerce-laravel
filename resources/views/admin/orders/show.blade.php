@extends('layouts.admin')
@section('title', 'Order #' . $order->id)
@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-6">Order #{{ $order->id }}</h1>
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="mb-4">
            <h2 class="text-lg font-semibold">Customer</h2>
            <p>{{ $order->user->name }}</p>
        </div>
        <div class="mb-4">
            <h2 class="text-lg font-semibold">Total</h2>
            <p>${{ number_format($order->total, 2) }}</p>
        </div>
        <div class="mb-4">
            <h2 class="text-lg font-semibold">Status</h2>
            <p>{{ $order->status }}</p>
        </div>
        <div class="mb-4">
            <h2 class="text-lg font-semibold">Items</h2>
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3 text-left">Product</th>
                        <th class="p-3 text-left">Quantity</th>
                        <th class="p-3 text-left">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="p-3">{{ $item->product->name }}</td>
                            <td class="p-3">{{ $item->quantity }}</td>
                            <td class="p-3">${{ number_format($item->price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <form action="{{ route('admin.orders.update', $order) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium">Update Status</label>
                <select name="status" class="w-full border rounded p-2">
                    <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                    <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update Status</button>
        </form>
    </div>
</div>
@endsection