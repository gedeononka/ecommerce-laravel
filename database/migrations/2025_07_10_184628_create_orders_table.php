<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // database/migrations/xxxx_create_orders_table.php
public function up()
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('order_number')->unique();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
        $table->enum('payment_method', ['online', 'cash_on_delivery']);
        $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
        $table->decimal('subtotal', 10, 2);
        $table->decimal('tax_amount', 10, 2)->default(0);
        $table->decimal('shipping_amount', 10, 2)->default(0);
        $table->decimal('discount_amount', 10, 2)->default(0);
        $table->decimal('total', 10, 2);
        
        // Adresse de livraison
        $table->string('shipping_first_name');
        $table->string('shipping_last_name');
        $table->string('shipping_address');
        $table->string('shipping_city');
        $table->string('shipping_postal_code');
        $table->string('shipping_country');
        $table->string('shipping_phone')->nullable();
        
        $table->text('notes')->nullable();
        $table->timestamp('shipped_at')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamps();
        
        $table->index('order_number');
        $table->index(['user_id', 'status']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
