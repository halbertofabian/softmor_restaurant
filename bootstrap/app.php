<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add SetTimezone to web middleware group
        $middleware->web(append: [
            \App\Http\Middleware\SetTimezone::class,
        ]);

        // Apply the "api" rate limiter to all API routes
        $middleware->throttleApi();

        // Register middleware aliases
        $middleware->alias([
            'branch' => \App\Http\Middleware\EnsureBranchSelected::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'cash.register' => \App\Http\Middleware\EnsureCashRegisterOpen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Demasiados intentos. Espera un momento e inténtalo de nuevo.',
                ], 429);
            }
        });
    })->create();
