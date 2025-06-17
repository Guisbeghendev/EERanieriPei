<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\CheckPermission; // <-- Adicione esta linha

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Registre o middleware do Inertia aqui no grupo 'web'
        $middleware->web(append: [
            HandleInertiaRequests::class, // Correto, adiciona o middleware Inertia ao grupo 'web'
        ]);

        // Registre seus aliases de middleware aqui
        $middleware->alias([
            // SEU NOVO MIDDLEWARE PERSONALIZADO
            'check.permission' => CheckPermission::class, // Usando o alias para o seu middleware

            // Outros aliases padrão ou de pacotes como Spatie (se você os estiver usando)
            // 'auth' => \App\Http\Middleware\Authenticate::class,
            // 'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            // 'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            // 'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
            // 'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
            // 'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
