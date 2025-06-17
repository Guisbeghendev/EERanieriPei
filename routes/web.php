<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
// Importações de Controllers de autenticação
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
// Importar o DashboardController
use App\Http\Controllers\DashboardController;
// Importar o ProfileController
use App\Http\Controllers\ProfileController;

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

// --- ROTAS DE AUTENTICAÇÃO (Breeze Padrão) ---
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

// --- ROTAS AUTENTICADAS ---
Route::middleware('auth')->group(function () {
    // Rota de Logout
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Rotas de verificação de e-mail (Laravel Breeze padrão)
    Route::get('verify-email', [VerifyEmailController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [VerifyEmailController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Rota do Dashboard
    Route::get('/dashboard', DashboardController::class)
        ->middleware(['verified'])
        ->name('dashboard');

    //---
    ###Rotas de Perfil (Atualizadas)

    // Rota para exibir o perfil do usuário
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');

    // Rota para exibir o formulário de edição do perfil
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');

    // Rota para atualizar o perfil (incluindo avatar e dados do profile)
    // O Inertia.js envia como POST e usa _method=patch, que o Laravel interpreta como PATCH.
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Rota para deletar a conta
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rotas específicas de fotógrafo
    Route::middleware('role:fotografo')->group(function () {
        Route::get('/fotografo/dashboard', function () {
            return Inertia::render('Fotografo/Dashboard');
        })->name('fotografo.dashboard');
    });

    // Rotas específicas de administrador
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('admin.dashboard');
    });

    // Rota para galerias, acessível apenas por usuários autenticados
    Route::get('/galleries', function () {
        return Inertia::render('Galleries/Index');
    })->name('galleries.index');

    // Rota para confirmar senha (necessária para acesso a certas seções ou ações sensíveis)
    // Mantida para consistência com o Breeze, mesmo que não diretamente usada pelo seu Profile/Edit.vue principal.
    Route::get('confirm-password', [AuthenticatedSessionController::class, 'confirmPassword'])
        ->name('password.confirm');
    Route::post('confirm-password', [AuthenticatedSessionController::class, 'storeConfirmedPassword']);
});
