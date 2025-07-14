@extends('layouts.client')

@section('title', 'Produits - ' . config('app.name'))

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">Nos Produits</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Produits</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Filters & Search -->
    <div class="row mb-4">
        <div class="col-md-8">
            <form method="GET" action="{{ route('products.index') }}" class="d-flex gap-2">
                <input type="text" name="search" class="form-control" 
                       placeholder="Rechercher un produit..." 
                       value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        <div class="col-md-4">
            <form method="GET" action="{{ route('products.index') }}" class="d-flex gap-2">
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">Toutes les catégories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" 
                                {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="">Trier par</option>
                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>
                        Nom (A-Z)
                    </option>
                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>
                        Nom (Z-A)
                    </option>
                    <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>
                        Prix croissant
                    </option>
                    <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>
                        Prix décroissant
                    </option>
                </select>
            </form>
        </div>
    </div>
    
    <!-- Results Info -->
    <div class="row mb-3">
        <div class="col-12">
            <p class="text-muted">
                {{ $products->total() }} produit(s) trouvé(s)
                @if(request('search'))
                    pour "{{ request('search') }}"
                @endif
                @if(request('category'))
                    dans {{ $categories->find(request('category'))->name ?? 'cette catégorie' }}
                @endif
            </p>
        </div>
    </div>
    
    <!-- Products Grid -->
    <div class="row">
        @forelse($products as $product)
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                @include('components.product-card', ['product' => $product])
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h4>Aucun produit trouvé</h4>
                <p class="text-muted">Essayez de modifier vos critères de recherche</p>
                <a href="{{ route('products.index') }}" class="btn btn-primary">
                    Voir tous les produits
                </a>
            </div>
        @endforelse
    </div>
    
    <!-- Pagination -->
    @if($products->hasPages())
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Products pagination">
                    {{ $products->withQueryString()->links() }}
                </nav>
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    // Auto-submit form when filters change
    document.addEventListener('DOMContentLoaded', function() {
        const filterSelects = document.querySelectorAll('select[name="category"], select[name="sort"]');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    });
</script>
@endsection