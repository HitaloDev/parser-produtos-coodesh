<?php

use App\Http\Controllers\ApiStatusController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ApiStatusController::class, 'index']);

Route::middleware('api.key')->group(function () {
    Route::get('/products/search', [ProductSearchController::class, 'search']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{code}', [ProductController::class, 'show']);
    Route::put('/products/{code}', [ProductController::class, 'update']);
    Route::delete('/products/{code}', [ProductController::class, 'destroy']);
});
