<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return url('/product');
});

Route::get('/product', [ProductController::class, 'index']);
