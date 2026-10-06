<?php

use Illuminate\Support\Facades\Route;

Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/user', [\App\Http\Controllers\Api\AuthController::class, 'user']);
    Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);

    // Branches
    Route::get('/branches', [\App\Http\Controllers\Api\ApiBranchController::class, 'index']);
    Route::get('/branches/{branch}/verify', [\App\Http\Controllers\Api\ApiBranchController::class, 'verifyAccess']);

    // Dashboard (solo administradores)
    Route::get('/dashboard', [\App\Http\Controllers\Api\ApiDashboardController::class, 'index']);
    Route::get('/dashboard/waiter', [\App\Http\Controllers\Api\ApiDashboardController::class, 'waiter']);

    // Waiters
    Route::get('/waiters', [\App\Http\Controllers\Api\ApiWaiterController::class, 'index']);

    // Preparation areas
    Route::get('/preparation-areas', [\App\Http\Controllers\Api\ApiPreparationAreaController::class, 'index']);

    // Tables
    Route::get('/tables', [\App\Http\Controllers\Api\ApiTableController::class, 'index']);
    Route::put('/tables/{table}/occupy', [\App\Http\Controllers\Api\ApiTableController::class, 'occupy']);
    Route::put('/tables/{table}/release', [\App\Http\Controllers\Api\ApiTableController::class, 'release']);

    // Products
    Route::get('/products', [\App\Http\Controllers\Api\ApiProductController::class, 'index']);

    // Orders
    Route::get('/orders', [\App\Http\Controllers\Api\ApiOrderController::class, 'index']);
    Route::post('/orders/get-or-create', [\App\Http\Controllers\Api\ApiOrderController::class, 'getOrCreate']);
    Route::post('/orders/{order}/send', [\App\Http\Controllers\Api\ApiOrderController::class, 'send']);
    Route::post('/orders/{order}/mark-printed', [\App\Http\Controllers\Api\ApiOrderController::class, 'markPrinted']);
    Route::get('/orders/{order}/print-payload', [\App\Http\Controllers\Api\ApiOrderController::class, 'printPayload']);
    Route::get('/orders/{order}/pre-check', [\App\Http\Controllers\Api\ApiOrderController::class, 'preCheck']);
    Route::get('/orders/{order}', [\App\Http\Controllers\Api\ApiOrderController::class, 'show']);
    Route::post('/orders/{order}/items', [\App\Http\Controllers\Api\ApiOrderController::class, 'addItem']);
    Route::patch('/orders/{order}/items/{detail}', [\App\Http\Controllers\Api\ApiOrderController::class, 'updateItem']);
    Route::delete('/orders/{order}/items/{detail}', [\App\Http\Controllers\Api\ApiOrderController::class, 'removeItem']);
    Route::post('/orders/{order}/send-kitchen', [\App\Http\Controllers\Api\ApiOrderController::class, 'sendToKitchen']);
});

Route::post('/printer/raw', [\App\Http\Controllers\PosController::class, 'apiLocalPrint']);

Route::get('/print-agent/config', [\App\Http\Controllers\PrintAgentController::class, 'config']);
Route::get('/print-agent/jobs/next', [\App\Http\Controllers\PrintAgentController::class, 'next']);
Route::post('/print-agent/jobs/{job}/printed', [\App\Http\Controllers\PrintAgentController::class, 'printed']);
Route::post('/print-agent/jobs/{job}/failed', [\App\Http\Controllers\PrintAgentController::class, 'failed']);
