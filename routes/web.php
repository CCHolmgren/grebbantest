<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/product');

Route::get('/product', [ProductController::class, 'index']);
