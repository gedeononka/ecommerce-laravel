<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payements', function (Blueprint $table) {
            $table->id();
            
            // Relation avec la commande
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            
            // Informations de base du paiement
            $table->string('transaction_id')->unique();
            $table->enum('payment_method', [
                'credit_card', 
                'paypal', 
                'bank_transfer', 
                'stripe', 
                'cash_on_delivery'
            ]);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            
            // Statut du paiement
            $table->enum('status', [
                'pending', 
                'processing', 
                'completed', 
                'failed', 
                'cancelled', 
                'refunded'
            ])->default('pending');
            
            // Informations de la passerelle de paiement
            $table->string('gateway')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            
            // Montants et frais
            $table->decimal('fee_amount', 8, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            
            // Horodatage des événements
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            
            // Informations supplémentaires
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Pour stocker des infos comme IP, user agent, etc.
            
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index(['order_id', 'status']);
            $table->index('transaction_id');
            $table->index('gateway_transaction_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payements');
    }
};