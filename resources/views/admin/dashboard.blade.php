@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-6">Admin Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold">Total Products</h2>
            <p class="text-3xl">{{ $productsCount ?? 0 }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold">Total Orders</h2>
            <p class="text-3xl">{{ $ordersCount ?? 0 }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold">Total Users</h2>
            <p class="text-3xl">{{ $usersCount ?? 0 }}</p>
        </div>
    </div>
</div>
@endsection