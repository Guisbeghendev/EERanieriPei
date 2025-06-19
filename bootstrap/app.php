<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\CheckPermission;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Registre o middleware do Inertia aqui no grupo 'web'
        $middleware->web(append: [
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, // <-- CORREÇÃO: ADICIONADO AQUI
            HandleInertiaRequests::class,
        ]);

        // Registre seus aliases de middleware aqui
        $middleware->alias([
            'check.permission' => CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
