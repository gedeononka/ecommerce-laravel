<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_products_table.php
public function up()
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description');
        $table->text('short_description')->nullable();
        $table->decimal('price', 10, 2);
        $table->decimal('sale_price', 10, 2)->nullable();
        $table->integer('stock')->default(0);
        $table->string('sku')->nullable();
        $table->json('images')->nullable();
        $table->foreignId('category_id')->constrained()->onDelete('cascade');
        $table->enum('status', ['active', 'inactive', 'out_of_stock'])->default('active');
        $table->boolean('is_featured')->default(false);
        $table->json('attributes')->nullable(); // couleur, taille, etc.
        $table->decimal('weight', 8, 2)->nullable();
        $table->string('dimensions')->nullable();
        $table->timestamps();
        
        $table->index(['status', 'is_featured']);
        $table->index('category_id');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
