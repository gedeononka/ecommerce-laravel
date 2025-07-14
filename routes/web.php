<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
// routes/web.php

// Authentication Routes
Auth::routes();

// Routes Client (accessible aux utilisateurs connectés)
Route::middleware(['auth', 'client'])->group(function () {
    include_once __DIR__ . '/client.php';
});

// Routes Admin (accessible aux administrateurs)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    include_once __DIR__ . '/admin.php';
});