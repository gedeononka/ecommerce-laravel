@extends('layouts.client')

@section('title', 'Finaliser ma commande')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Finaliser ma commande</h4>
                </div>
                <div class="card-body">
                    <form id="checkout-form" method="POST" action="{{ route('orders.store') }}">
                        @csrf
                        
                        <!-- Informations de livraison -->
                        <div class="mb-4">
                            <h5>Adresse de livraison</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shipping_first_name">Prénom *</label>
                                        <input type="text" class="form-control @error('shipping_first_name') is-invalid @enderror" 
                                               id="shipping_first_name" name="shipping_first_name" 
                                               value="{{ old('shipping_first_name', auth()->user()->first_name) }}" required>
                                        @error('shipping_first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shipping_last_name">Nom *</label>
                                        <input type="text" class="form-control @error('shipping_last_name') is-invalid @enderror" 
                                               id="shipping_last_name" name="shipping_last_name" 
                                               value="{{ old('shipping_last_name', auth()->user()->last_name) }}" required>
                                        @error('shipping_last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="shipping_address">Adresse *</label>
                                <input type="text" class="form-control @error('shipping_address') is-invalid @enderror" 
                                       id="shipping_address" name="shipping_address" 
                                       value="{{ old('shipping_address', auth()->user()->address) }}" required>
                                @error('shipping_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shipping_city">Ville *</label>
                                        <input type="text" class="form-control @error('shipping_city') is-invalid @enderror" 
                                               id="shipping_city" name="shipping_city" 
                                               value="{{ old('shipping_city', auth()->user()->city) }}" required>
                                        @error('shipping_city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shipping_postal_code">Code postal *</label>
                                        <input type="text" class="form-control @error('shipping_postal_code') is-invalid @enderror" 
                                               id="shipping_postal_code" name="shipping_postal_code" 
                                               value="{{ old('shipping_postal_code', auth()->user()->postal_code) }}" required>
                                        @error('shipping_postal_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shipping_country">Pays *</label>
                                        <select class="form-control @error('shipping_country') is-invalid @enderror" 
                                                id="shipping_country" name="shipping_country" required>
                                            <option value="">Sélectionnez un pays</option>
                                            <option value="France" {{ old('shipping_country', auth()->user()->country) == 'France' ? 'selected' : '' }}>France</option>
                                            <option value="Belgique" {{ old('shipping_country', auth()->user()->country) == 'Belgique' ? 'selected' : '' }}>Belgique</option>
                                            <option value="Suisse" {{ old('shipping_country', auth()->user()->country) == 'Suisse' ? 'selected' : '' }}>Suisse</option>
                                            <option value="Luxembourg" {{ old('shipping_country', auth()->user()->country) == 'Luxembourg' ? 'selected' : '' }}>Luxembourg</option>
                                        </select>
                                        @error('shipping_country')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shipping_phone">Téléphone</label>
                                        <input type="tel" class="form-control @error('shipping_phone') is-invalid @enderror" 
                                               id="shipping_phone" name="shipping_phone" 
                                               value="{{ old('shipping_phone', auth()->user()->phone) }}">
                                        @error('shipping_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Méthode de paiement -->
                        <div class="mb-4">
                            <h5>Méthode de paiement</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="payment_online" value="online" {{ old('payment_method', 'online') == 'online' ? 'checked' : '' }}>
                                <label class="form-check-label" for="payment_online">
                                    <i class="fas fa-credit-card"></i> Paiement en ligne
                                    <small class="d-block text-muted">Carte bancaire, PayPal</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="payment_cod" value="cash_on_delivery" {{ old('payment_method') == 'cash_on_delivery' ? 'checked' : '' }}>
                                <label class="form-check-label" for="payment_cod">
                                    <i class="fas fa-truck"></i> Paiement à la livraison
                                    <small class="d-block text-muted">Espèces ou carte à la réception</small>
                                </label>
                            </div>
                            @error('payment_method')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Notes -->
                        <div class="mb-4">
                            <h5>Notes de commande <small class="text-muted">(optionnel)</small></h5>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3" 
                                      placeholder="Instructions spéciales pour la livraison...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Conditions générales -->
                        <div class="form-check mb-4">
                            <input class="form-check-input @error('terms') is-invalid @enderror" 
                                   type="checkbox" id="terms" name="terms" {{ old('terms') ? 'checked' : '' }}>
                            <label class="form-check-label" for="terms">
                                J'accepte les <a href="#" target="_blank">conditions générales de vente</a> *
                            </label>
                            @error('terms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('cart.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Retour au panier
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg" id="submit-order">
                                <i class="fas fa-check"></i> Confirmer ma commande
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Résumé de commande -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Résumé de commande</h5>
                </div>
                <div class="card-body">
                    <!-- Articles du panier -->
                    @foreach($cartItems as $item)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="mb-0">{{ $item->product->name }}</h6>
                                <small class="text-muted">Quantité: {{ $item->quantity }}</small>
                            </div>
                            <span>{{ number_format($item->product->final_price * $item->quantity, 2) }} €</span>
                        </div>
                    @endforeach
                    
                    <hr>
                    
                    <!-- Totaux -->
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sous-total:</span>
                        <span>{{ number_format($subtotal, 2) }} €</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Frais de livraison:</span>
                        <span>{{ number_format($shipping, 2) }} €</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>TVA (20%):</span>
                        <span>{{ number_format($tax, 2) }} €</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total:</strong>
                        <strong>{{ number_format($total, 2) }} €</strong>
                    </div>
                </div>
            </div>
            
            <!-- Informations de livraison -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6><i class="fas fa-truck"></i> Livraison</h6>
                    <p class="text-muted mb-0">
                        Livraison standard: 3-5 jours ouvrés<br>
                        Livraison express: 24-48h (disponible au checkout)
                    </p>
                </div>
            </div>
            
            <!-- Sécurité -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6><i class="fas fa-shield-alt"></i> Paiement sécurisé</h6>
                    <p class="text-muted mb-0">
                        Vos données sont protégées par un cryptage SSL
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('checkout-form');
        const submitButton = document.getElementById('submit-order');
        
        // Validation du formulaire
        form.addEventListener('submit', function(e) {
            const termsCheckbox = document.getElementById('terms');
            if (!termsCheckbox.checked) {
                e.preventDefault();
                alert('Vous devez accepter les conditions générales de vente.');
                return;
            }
            
            // Désactiver le bouton pour éviter les doubles soumissions
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement en cours...';
        });
        
        // Calculer automatiquement les frais de livraison selon le pays
        document.getElementById('shipping_country').addEventListener('change', function() {
            const country = this.value;
            // Ici, vous pouvez ajuster les frais de livraison selon le pays
            // et mettre à jour l'affichage en temps réel
        });
    });
</script>
@endsection