<?php

// routes/client.php - Zone Membre 2
Route::get('/products', [App\Http\Controllers\Client\ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [App\Http\Controllers\Client\ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [App\Http\Controllers\Client\CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [App\Http\Controllers\Client\CartController::class, 'add'])->name('cart.add');
Route::delete('/cart/{item}', [App\Http\Controllers\Client\CartController::class, 'remove'])->name('cart.remove');

Route::get('/checkout', [App\Http\Controllers\Client\OrderController::class, 'checkout'])->name('checkout');
Route::post('/orders', [App\Http\Controllers\Client\OrderController::class, 'store'])->name('orders.store');
Route::get('/orders', [App\Http\Controllers\Client\OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{order}', [App\Http\Controllers\Client\OrderController::class, 'show'])->name('orders.show');

Route::get('/profile', [App\Http\Controllers\Client\ProfileController::class, 'index'])->name('profile.index');
Route::put('/profile', [App\Http\Controllers\Client\ProfileController::class, 'update'])->name('profile.update');