<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;

// Handle preflight requests
Route::options('{any}', function() {
    return response('', 200)
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN')
        ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
        ->header('Access-Control-Allow-Credentials', 'true');
})->where('any', '.*');

// Public routes
Route::get('/products', [ProductController::class, 'index']); // Make products viewable to all

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
});

// Auth routes
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum'); 