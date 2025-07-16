@extends('layouts.admin')
@section('title', 'Orders')
@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-6">Orders</h1>
    <table class="w-full bg-white shadow rounded">
        <thead>
            <tr class="bg-gray-100">
                <th class="p-3 text-left">Order ID</th>
                <th class="p-3 text-left">Customer</th>
                <th class="p-3 text-left">Total</th>
                <th class="p-3 text-left">Status</th>
                <th class="p-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td class="p-3">{{ $order->id }}</td>
                    <td class="p-3">{{ $order->user->name }}</td>
                    <td class="p-3">${{ number_format($order->total, 2) }}</td>
                    <td class="p-3">{{ $order->status }}</td>
                    <td class="p-3">
                        <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-500">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="p-3 text-center">No orders found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection