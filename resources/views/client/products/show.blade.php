@extends('layouts.client')

@section('title', $product->name . ' - ' . config('app.name'))

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Produits</a></li>
            <li class="breadcrumb-item"><a href="{{ route('products.index', ['category' => $product->category->id]) }}">
                {{ $product->category->name }}
            </a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Product Images -->
        <div class="col-lg-6 mb-4">
            <div class="product-images">
                <div class="main-image mb-3">
                    <img src="{{ asset('storage/' . $product->main_image) }}" 
                         alt="{{ $product->name }}" 
                         class="img-fluid rounded border"
                         id="mainImage"
                         style="width: 100%; height: 400px; object-fit: cover;">
                </div>
                
                @if($product->images && count($product->images) > 1)
                    <div class="row">
                        @foreach($product->images as $image)
                            <div class="col-3 mb-2">
                                <img src="{{ asset('storage/' . $image) }}" 
                                     alt="{{ $product->name }}" 
                                     class="img-fluid rounded border thumbnail-image"
                                     style="height: 80px; object-fit: cover; cursor: pointer;"
                                     onclick="changeMainImage(this.src)">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Product Details -->
        <div class="col-lg-6">
            <div class="product-details">
                <h1 class="h2 fw-bold mb-3">{{ $product->name }}</h1>
                
                <!-- Price -->
                <div class="price-section mb-3">
                    @if($product->sale_price)
                        <span class="h3 text-danger fw-bold">{{ number_format($product->sale_price, 2) }} €</span>
                        <span class="text-muted text-decoration-line-through ms-2">{{ number_format($product->price, 2) }} €</span>
                        <span class="badge bg-danger ms-2">
                            -{{ round((($product->price - $product->sale_price) / $product->price) * 100) }}%
                        </span>
                    @else
                        <span class="h3 text-primary fw-bold">{{ number_format($product->price, 2) }} €</span>
                    @endif
                </div>
                
                <!-- Short Description -->
                @if($product->short_description)
                    <div class="short-description mb-3">
                        <p class="text-muted">{{ $product->short_description }}</p>
                    </div>
                @endif
                
                <!-- Stock Status -->
                <div class="stock-status mb-3">
                    @if($product->isInStock())
                        <span class="badge bg-success">
                            <i class="fas fa-check-circle"></i> En stock ({{ $product->stock }} disponible(s))
                        </span>
                    @else
                        <span class="badge bg-danger">
                            <i class="fas fa-times-circle"></i> Rupture de stock
                        </span>
                    @endif
                </div>
                
                <!-- Add to Cart Form -->
                @if($product->isInStock())
                    <form action="{{ route('cart.add') }}" method="POST" class="mb-4">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="quantity" class="form-label">Quantité</label>
                                <input type="number" 
                                       name="quantity" 
                                       id="quantity" 
                                       class="form-control" 
                                       min="1" 
                                       max="{{ $product->stock }}" 
                                       value="1" 
                                       required>
                            </div>
                            <div class="col-md-8">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-cart-plus me-2"></i>Ajouter au panier
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
                
                <!-- Product Attributes -->
                @if($product->attributes)
                    <div class="product-attributes mb-4">
                        <h5>Caractéristiques</h5>
                        <ul class="list-unstyled">
                            @foreach($product->attributes as $key => $value)
                                <li><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <!-- Product Details -->
                <div class="product-info">
                    <ul class="list-unstyled">
                        <li><strong>Catégorie:</strong> {{ $product->category->name }}</li>
                        @if($product->sku)
                            <li><strong>SKU:</strong> {{ $product->sku }}</li>
                        @endif
                        @if($product->weight)
                            <li><strong>Poids:</strong> {{ $product->weight }} kg</li>
                        @endif
                        @if($product->dimensions)
                            <li><strong>Dimensions:</strong> {{ $product->dimensions }}</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Description -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Description du produit</h4>
                </div>
                <div class="card-body">
                    {!! nl2br(e($product->description)) !!}
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    @if($relatedProducts->count() > 0)
        <div class="row mt-5">
            <div class="col-12">
                <h4 class="mb-4">Produits similaires</h4>
                <div class="row">
                    @foreach($relatedProducts as $relatedProduct)
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            @include('components.product-card', ['product' => $relatedProduct])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    function changeMainImage(src) {
        document.getElementById('mainImage').src = src;
    }
    
    // Image zoom on hover
    document.addEventListener('DOMContentLoaded', function() {
        const mainImage = document.getElementById('mainImage');
        
        mainImage.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        mainImage.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
</script>
@endsection