@extends('layouts.client')

@section('title', 'Mes Commandes')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Mes Commandes</h2>
                <a href="{{ route('products.index') }}" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Continuer mes achats
                </a>
            </div>

            @if($orders->count() > 0)
                <div class="row">
                    @foreach($orders as $order)
                        <div class="col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0">Commande #{{ $order->order_number }}</h5>
                                        <small class="text-muted">{{ $order->created_at->format('d/m/Y à H:i') }}</small>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-{{ $order->status == 'delivered' ? 'success' : ($order->status == 'cancelled' ? 'danger' : 'primary') }} p-2">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Détails de la commande</h6>
                                            <p class="mb-1"><strong>Statut:</strong> 
                                                @switch($order->status)
                                                    @case('pending')
                                                        <span class="text-warning">En attente</span>
                                                        @break
                                                    @case('processing')
                                                        <span class="text-info">En cours de traitement</span>
                                                        @break
                                                    @case('shipped')
                                                        <span class="text-primary">Expédiée</span>
                                                        @break
                                                    @case('delivered')
                                                        <span class="text-success">Livrée</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="text-danger">Annulée</span>
                                                        @break
                                                @endswitch
                                            </p>
                                            <p class="mb-1"><strong>Paiement:</strong> 
                                                @switch($order->payment_status)
                                                    @case('pending')
                                                        <span class="text-warning">En attente</span>
                                                        @break
                                                    @case('paid')
                                                        <span class="text-success">Payé</span>
                                                        @break
                                                    @case('failed')
                                                        <span class="text-danger">Échoué</span>
                                                        @break
                                                    @case('refunded')
                                                        <span class="text-info">Remboursé</span>
                                                        @break
                                                @endswitch
                                            </p>
                                            <p class="mb-1"><strong>Total:</strong> {{ number_format($order->total, 2) }} €</p>
                                            <p class="mb-1"><strong>Articles:</strong> {{ $order->items->count() }}</p>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <h6>Adresse de livraison</h6>
                                            <address>
                                                {{ $order->shipping_full_name }}<br>
                                                {{ $order->shipping_address }}<br>
                                                {{ $order->shipping_postal_code }} {{ $order->shipping_city }}<br>
                                                {{ $order->shipping_country }}
                                                @if($order->shipping_phone)
                                                    <br>Tél: {{ $order->shipping_phone }}
                                                @endif
                                            </address>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <h6>Articles commandés</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Produit</th>
                                                            <th>Prix unitaire</th>
                                                            <th>Quantité</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($order->items as $item)
                                                            <tr>
                                                                <td>{{ $item->product->name }}</td>
                                                                <td>{{ number_format($item->price, 2) }} €</td>
                                                                <td>{{ $item->quantity }}</td>
                                                                <td>{{ number_format($item->total, 2) }} €</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer d-flex justify-content-between">
                                    <div>
                                        @if($order->canBeCancelled())
                                            <form method="POST" action="{{ route('orders.cancel', $order) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('Êtes-vous sûr de vouloir annuler cette commande?')">
                                                    Annuler la commande
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-primary">
                                            Voir les détails
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $orders->links() }}
                </div>
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                        <h4>Aucune commande trouvée</h4>
                        <p class="text-muted">Vous n'avez pas encore passé de commande.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-primary">
                            Découvrir nos produits
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Animation pour les cartes de commandes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>
@endsection