@extends('layouts.client')

@section('title', 'Accueil - ' . config('app.name'))

@section('content')
<div class="container-fluid p-0">
    <!-- Hero Section -->
    <section class="hero-section bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Bienvenue dans notre boutique</h1>
                    <p class="lead mb-4">Découvrez nos produits de qualité à des prix exceptionnels</p>
                    <a href="{{ route('products.index') }}" class="btn btn-light btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Voir les produits
                    </a>
                </div>
                <div class="col-lg-6">
                    <img src="{{ asset('images/hero-image.jpg') }}" alt="Hero" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Featured Products -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="fw-bold">Produits en Vedette</h2>
                    <p class="text-muted">Découvrez nos produits les plus populaires</p>
                </div>
            </div>
            
            <div class="row">
                @forelse($featuredProducts as $product)
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        @include('components.product-card', ['product' => $product])
                    </div>
                @empty
                    <div class="col-12 text-center">
                        <p class="text-muted">Aucun produit en vedette pour le moment</p>
                    </div>
                @endforelse
            </div>
            
            @if($featuredProducts->count() > 0)
                <div class="text-center mt-4">
                    <a href="{{ route('products.index') }}" class="btn btn-primary">
                        Voir tous les produits
                    </a>
                </div>
            @endif
        </div>
    </section>
    
    <!-- Categories -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="fw-bold">Catégories</h2>
                    <p class="text-muted">Explorez nos différentes catégories</p>
                </div>
            </div>
            
            <div class="row">
                @foreach($categories as $category)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            @if($category->image)
                                <img src="{{ asset('storage/' . $category->image) }}" 
                                     alt="{{ $category->name }}" 
                                     class="card-img-top" 
                                     style="height: 200px; object-fit: cover;">
                            @endif
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">{{ $category->name }}</h5>
                                <p class="card-text text-muted">{{ $category->description }}</p>
                                <a href="{{ route('products.index', ['category' => $category->id]) }}" 
                                   class="btn btn-outline-primary mt-auto">
                                    Voir les produits
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-shipping-fast fa-3x text-primary mb-3"></i>
                            <h5>Livraison Rapide</h5>
                            <p class="text-muted">Livraison sous 48-72h</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                            <h5>Paiement Sécurisé</h5>
                            <p class="text-muted">Transactions 100% sécurisées</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-undo fa-3x text-info mb-3"></i>
                            <h5>Retour Facile</h5>
                            <p class="text-muted">30 jours pour changer d'avis</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-headset fa-3x text-warning mb-3"></i>
                            <h5>Support 24/7</h5>
                            <p class="text-muted">Assistance client disponible</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection