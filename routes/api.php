<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/user', [\App\Http\Controllers\Api\AuthController::class, 'user']);

    // Branches
    Route::get('/branches', [\App\Http\Controllers\Api\ApiBranchController::class, 'index']);
    Route::get('/branches/{branch}/verify', [\App\Http\Controllers\Api\ApiBranchController::class, 'verifyAccess']);

    // Tables
    Route::get('/tables', [\App\Http\Controllers\Api\ApiTableController::class, 'index']);
    Route::put('/tables/{table}/occupy', [\App\Http\Controllers\Api\ApiTableController::class, 'occupy']);
    Route::put('/tables/{table}/release', [\App\Http\Controllers\Api\ApiTableController::class, 'release']);
    
    // Products
    Route::get('/products', [\App\Http\Controllers\Api\ApiProductController::class, 'index']);

    // Orders
    Route::post('/orders/get-or-create', [\App\Http\Controllers\Api\ApiOrderController::class, 'getOrCreate']);
    Route::get('/orders/{order}', [\App\Http\Controllers\Api\ApiOrderController::class, 'show']);
    Route::post('/orders/{order}/items', [\App\Http\Controllers\Api\ApiOrderController::class, 'addItem']);
    Route::delete('/orders/{order}/items/{detail}', [\App\Http\Controllers\Api\ApiOrderController::class, 'removeItem']);
    Route::post('/orders/{order}/send-kitchen', [\App\Http\Controllers\Api\ApiOrderController::class, 'sendToKitchen']);
});

Route::post('/printer/raw', [\App\Http\Controllers\PosController::class, 'apiLocalPrint']);
