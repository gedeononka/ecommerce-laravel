@extends('layouts.client')

@section('title', 'Commande #' . $order->order_number)

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <!-- En-tête de la commande -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Commande #{{ $order->order_number }}</h4>
                    <span class="badge badge-{{ $order->status == 'completed' ? 'success' : ($order->status == 'pending' ? 'warning' : ($order->status == 'cancelled' ? 'danger' : 'info')) }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Date de commande:</strong> {{ $order->created_at->format('d/m/Y à H:i') }}</p>
                            <p><strong>Statut:</strong> 
                                @switch($order->status)
                                    @case('pending')
                                        <span class="text-warning">En attente</span>
                                        @break
                                    @case('confirmed')
                                        <span class="text-info">Confirmée</span>
                                        @break
                                    @case('processing')
                                        <span class="text-primary">En préparation</span>
                                        @break
                                    @case('shipped')
                                        <span class="text-info">Expédiée</span>
                                        @break
                                    @case('delivered')
                                        <span class="text-success">Livrée</span>
                                        @break
                                    @case('cancelled')
                                        <span class="text-danger">Annulée</span>
                                        @break
                                    @default
                                        <span class="text-muted">{{ ucfirst($order->status) }}</span>
                                @endswitch
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Méthode de paiement:</strong> 
                                @if($order->payment_method == 'online')
                                    <i class="fas fa-credit-card"></i> Paiement en ligne
                                @else
                                    <i class="fas fa-truck"></i> Paiement à la livraison
                                @endif
                            </p>
                            <p><strong>Statut du paiement:</strong> 
                                @switch($order->payment_status)
                                    @case('paid')
                                        <span class="text-success">Payée</span>
                                        @break
                                    @case('pending')
                                        <span class="text-warning">En attente</span>
                                        @break
                                    @case('failed')
                                        <span class="text-danger">Échoué</span>
                                        @break
                                    @default
                                        <span class="text-muted">{{ ucfirst($order->payment_status) }}</span>
                                @endswitch
                            </p>
                        </div>
                    </div>
                    
                    @if($order->notes)
                        <div class="mt-3">
                            <strong>Notes de commande:</strong>
                            <p class="text-muted">{{ $order->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Articles commandés -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Articles commandés</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Prix unitaire</th>
                                    <th>Quantité</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->orderItems as $item)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($item->product->image)
                                                    <img src="{{ asset('storage/' . $item->product->image) }}" 
                                                         alt="{{ $item->product->name }}" 
                                                         class="img-thumbnail me-3" 
                                                         style="width: 60px; height: 60px; object-fit: cover;">
                                                @endif
                                                <div>
                                                    <h6 class="mb-0">{{ $item->product->name }}</h6>
                                                    <small class="text-muted">{{ $item->product->category->name ?? 'Non catégorisé' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ number_format($item->price, 2) }} €</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->price * $item->quantity, 2) }} €</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Suivi de commande -->
            @if($order->status != 'cancelled')
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Suivi de commande</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item {{ $order->status == 'pending' ? 'active' : 'completed' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Commande passée</h6>
                                    <p class="text-muted">{{ $order->created_at->format('d/m/Y à H:i') }}</p>
                                </div>
                            </div>
                            
                            <div class="timeline-item {{ $order->status == 'confirmed' ? 'active' : ($order->status == 'processing' || $order->status == 'shipped' || $order->status == 'delivered' ? 'completed' : '') }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Commande confirmée</h6>
                                    @if($order->status != 'pending')
                                        <p class="text-muted">{{ $order->updated_at->format('d/m/Y à H:i') }}</p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="timeline-item {{ $order->status == 'processing' ? 'active' : ($order->status == 'shipped' || $order->status == 'delivered' ? 'completed' : '') }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>En préparation</h6>
                                    @if($order->status == 'processing' || $order->status == 'shipped' || $order->status == 'delivered')
                                        <p class="text-muted">Votre commande est en cours de préparation</p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="timeline-item {{ $order->status == 'shipped' ? 'active' : ($order->status == 'delivered' ? 'completed' : '') }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Expédiée</h6>
                                    @if($order->status == 'shipped' || $order->status == 'delivered')
                                        <p class="text-muted">Votre commande a été expédiée</p>
                                        @if($order->tracking_number)
                                            <p><strong>Numéro de suivi:</strong> {{ $order->tracking_number }}</p>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            
                            <div class="timeline-item {{ $order->status == 'delivered' ? 'active completed' : '' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Livrée</h6>
                                    @if($order->status == 'delivered')
                                        <p class="text-muted">Votre commande a été livrée</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Sidebar avec résumé et adresses -->
        <div class="col-md-4">
            <!-- Résumé de commande -->
            <div class="card">
                <div class="card-header">
                    <h5>Résumé de commande</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sous-total:</span>
                        <span>{{ number_format($order->subtotal, 2) }} €</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Frais de livraison:</span>
                        <span>{{ number_format($order->shipping_cost, 2) }} €</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>TVA:</span>
                        <span>{{ number_format($order->tax_amount, 2) }} €</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total:</strong>
                        <strong>{{ number_format($order->total_amount, 2) }} €</strong>
                    </div>
                </div>
            </div>
            
            <!-- Adresse de livraison -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-shipping-fast"></i> Adresse de livraison</h6>
                </div>
                <div class="card-body">
                    <address>
                        <strong>{{ $order->shipping_first_name }} {{ $order->shipping_last_name }}</strong><br>
                        {{ $order->shipping_address }}<br>
                        {{ $order->shipping_postal_code }} {{ $order->shipping_city }}<br>
                        {{ $order->shipping_country }}<br>
                        @if($order->shipping_phone)
                            <abbr title="Téléphone">Tél:</abbr> {{ $order->shipping_phone }}
                        @endif
                    </address>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card mt-3">
                <div class="card-body">
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-primary btn-block mb-2">
                        <i class="fas fa-list"></i> Mes commandes
                    </a>
                    
                    @if($order->status == 'pending' || $order->status == 'confirmed')
                        <button type="button" class="btn btn-outline-danger btn-block mb-2" onclick="confirmCancel()">
                            <i class="fas fa-times"></i> Annuler la commande
                        </button>
                    @endif
                    
                    @if($order->status == 'delivered')
                        <a href="{{ route('orders.invoice', $order) }}" class="btn btn-outline-success btn-block mb-2" target="_blank">
                            <i class="fas fa-file-invoice"></i> Télécharger la facture
                        </a>
                    @endif
                    
                    <a href="{{ route('contact') }}" class="btn btn-outline-info btn-block">
                        <i class="fas fa-envelope"></i> Contacter le support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation d'annulation -->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Annuler la commande</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir annuler cette commande ?</p>
                <p class="text-muted">Cette action ne peut pas être annulée.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Non, garder la commande</button>
                <form method="POST" action="{{ route('orders.cancel', $order) }}" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-danger">Oui, annuler</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .timeline {
        position: relative;
        padding: 20px 0;
    }
    
    .timeline-item {
        position: relative;
        padding-left: 40px;
        margin-bottom: 30px;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 25px;
        height: 100%;
        width: 2px;
        background: #e9ecef;
    }
    
    .timeline-item:last-child::before {
        display: none;
    }
    
    .timeline-marker {
        position: absolute;
        left: 10px;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #e9ecef;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #e9ecef;
    }
    
    .timeline-item.active .timeline-marker {
        background: #007bff;
        box-shadow: 0 0 0 2px #007bff;
    }
    
    .timeline-item.completed .timeline-marker {
        background: #28a745;
        box-shadow: 0 0 0 2px #28a745;
    }
    
    .timeline-content h6 {
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .timeline-item.active .timeline-content h6 {
        color: #007bff;
    }
    
    .timeline-item.completed .timeline-content h6 {
        color: #28a745;
    }
    
    .btn-block {
        width: 100%;
    }
    
    .img-thumbnail {
        border-radius: 8px;
    }
    
    .badge {
        font-size: 0.9em;
    }
</style>
@endsection

@section('scripts')
<script>
    function confirmCancel() {
        $('#cancelModal').modal('show');
    }
    
    // Afficher un message de confirmation après annulation
    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif
    
    @if(session('error'))
        toastr.error('{{ session('error') }}');
    @endif
</script>
@endsection