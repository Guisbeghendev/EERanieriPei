<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
// Importações de Controllers de autenticação que agora serão usados
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController; // Para a verificação de e-mail

// --- ROTAS PÚBLICAS ---
Route::get('/', function () {
    return Inertia::render('Home');
});

// Outras rotas públicas
Route::get('/sobre-a-escola', function () {
    return Inertia::render('Sobre/SobreEscola');
})->name('sobre-a-escola');

Route::get('/gremio', function () {
    return Inertia::render('Gremio/Gremio');
})->name('gremio');

Route::get('/brincando-dialogando', function () {
    return Inertia::render('BrincandoDialogando/BrincandoDialogando');
})->name('brincando-dialogando');

Route::get('/simoninhanacozinha', function () {
    return Inertia::render('Simoninhanacozinha/Simoninhanacozinha');
})->name('simoninhanacozinha');

Route::get('/coral-ranieri', function () {
    return Inertia::render('Coral/CoralRanieri');
})->name('coral-ranieri');

// --- ROTAS DE AUTENTICAÇÃO (Breeze Padrão - ATIVADAS) ---
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

// --- ROTAS AUTENTICADAS (COM LOGOUT E VERIFICAÇÃO DE E-MAIL) ---
Route::middleware('auth')->group(function () {
    // Rota de Logout (ATIVADA)
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Rotas de verificação de e-mail (Laravel Breeze padrão)
    Route::get('verify-email', [VerifyEmailController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [VerifyEmailController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Rota para confirmar senha (necessária para acesso a certas seções ou ações sensíveis)
    Route::get('confirm-password', [AuthenticatedSessionController::class, 'confirmPassword'])
        ->name('password.confirm');
    Route::post('confirm-password', [AuthenticatedSessionController::class, 'storeConfirmedPassword']);

    // Rota do Dashboard (ainda será implementada, mas já ativada para acesso)
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard'); // Assumindo que você terá uma Dashboard.vue
    })->middleware(['verified'])->name('dashboard');

    // Exemplo de rota protegida que pode ter verificação de e-mail
    Route::get('/profile', function () {
        return Inertia::render('Profile/Show'); // Apenas um exemplo
    })->name('profile.show');

    // Rotas específicas de fotógrafo
    Route::middleware('role:fotografo')->group(function () {
        Route::get('/fotografo/dashboard', function () {
            return Inertia::render('Fotografo/Dashboard');
        })->name('fotografo.dashboard');
        // Outras rotas do fotógrafo...
    });

    // Rotas específicas de administrador
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('admin.dashboard');
        // Outras rotas do administrador...
    });

    // Rota para galerias, acessível apenas por usuários autenticados
    Route::get('/galleries', function () {
        return Inertia::render('Galleries/Index'); // Assumindo uma página de galerias
    })->name('galleries.index');
});
