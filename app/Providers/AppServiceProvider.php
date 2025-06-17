<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Geralmente vazio. Adicione aqui bindings de containers, etc.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Geralmente vazio. Adicione aqui lógica que precisa ser executada na inicialização do app.
    }
}
