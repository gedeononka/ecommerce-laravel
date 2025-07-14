@extends('layouts.client')

@section('title', 'Mon Panier - ' . config('app.name'))

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">Mon Panier</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Panier</li>
                </ol>
            </nav>
        </div>
    </div>
    
    @if($cartItems->count() > 0)
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Articles dans votre panier ({{ $cartItems->count() }})</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produit</th>
                                        <th>Prix unitaire</th>
                                        <th>Quantité</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cartItems as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="{{ asset('storage/' . $item->product->main_image) }}" 
                                                         alt="{{ $item->product->name }}" 
                                                         class="me-3 rounded"
                                                         style="width: 60px; height: 60px; object-fit: cover;">
                                                    <div>
                                                        <h6 class="mb-0">{{ $item->product->name }}</h6>
                                                        <small class="text-muted">{{ $item->product->category->name }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($item->product->sale_price)
                                                    <span class="text-danger fw-bold">{{ number_format($item->product->sale_price, 2) }} €</span>
                                                    <br>
                                                    <small class="text-muted text-decoration-line-through">{{ number_format($item->product->price, 2) }} €</small>
                                                @else
                                                    <span class="fw-bold">{{ number_format($item->product->price, 2) }} €</span>
                                                @endif
                                            </td>
                                            <td>
                                                <form action="{{ route('cart.update', $item->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="input-group" style="width: 120px;">
                                                        <input type="number" 
                                                               name="quantity" 
                                                               value="{{ $item->quantity }}" 
                                                               min="1" 
                                                               max="{{ $item->product->stock }}"
                                                               class="form-control form-control-sm"
                                                               onchange="this.form.submit()">
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td>
                                                <span class="fw-bold">{{ number_format($item->product->final_price * $item->quantity, 2) }} €</span>
                                            </td>
                                            <td>
                                                <form action="{{ route('cart.remove', $item->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-outline-danger btn-sm"
                                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Cart Actions -->
                <div class="mt-3">
                    <a href="{{ route('products.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Continuer les achats
                    </a>
                    <form action="{{ route('cart.clear') }}" method="POST" class="d-inline ms-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="btn btn-outline-danger"
                                onclick="return confirm('Êtes-vous sûr de vouloir vider votre panier ?')">
                            <i class="fas fa-trash me-2"></i>Vider le panier
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Cart Summary -->
            <div class="col-lg