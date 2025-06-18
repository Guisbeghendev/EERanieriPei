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

// NOVOS IMPORTS
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\FotografoDashboardController;
use App\Http\Controllers\Fotografo\GalleryController; // Do subdiretório

// IMPORTS PARA ADMINISTRADOR
use App\Http\Controllers\Admin\GroupController; // Importa o GroupController
use App\Http\Controllers\Admin\UserGroupAssignmentController; // Importa o UserGroupAssignmentController
use App\Http\Controllers\Admin\RoleController; // Importa o RoleController
use App\Http\Controllers\Admin\UserController; // Importa o UserController

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

    // Rota do Dashboard Geral
    Route::get('/dashboard', DashboardController::class)
        ->middleware(['verified'])
        ->name('dashboard');

    // Rotas de Perfil do Usuário
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    // Para atualizar o perfil, Inertia.js envia POST com _method=patch, que Laravel interpreta como PATCH.
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rota para galerias (acessível por qualquer usuário autenticado)
    Route::get('/galleries', function () {
        return Inertia::render('Galleries/Index');
    })->name('galleries.index');

    // Rota para confirmar senha (necessária para acesso a certas seções ou ações sensíveis)
    Route::get('confirm-password', [AuthenticatedSessionController::class, 'confirmPassword'])
        ->name('password.confirm');
    Route::post('confirm-password', [AuthenticatedSessionController::class, 'storeConfirmedPassword']);

    // --- GRUPO DE ROTAS DO FOTÓGRAFO ---
    // Protegidas com o middleware 'check.permission' e a gate 'fotografo-only'
    Route::prefix('fotografo')->middleware(['check.permission:gate,fotografo-only'])->group(function () {
        Route::get('/dashboard', [FotografoDashboardController::class, 'index'])
            ->name('fotografo.dashboard');
        // Adicione outras rotas específicas do fotógrafo aqui, como gerenciamento de galerias/imagens
    });

    // --- GRUPO DE ROTAS DO ADMINISTRADOR ---
    // ADICIONADO ->name('admin.') AQUI PARA PREFIXAR OS NOMES DAS ROTAS
    Route::prefix('admin')->name('admin.')->middleware(['check.permission:gate,admin-only'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard'); // AGORA O NOME COMPLETO SERÁ 'admin.dashboard'

        // Rotas de Recurso para Grupos (CRUD) - Agora terão nomes como 'admin.groups.index', etc.
        Route::resource('groups', GroupController::class);

        // Rotas de Recurso para Papéis (CRUD) - Agora terão nomes como 'admin.roles.index', etc.
        Route::resource('roles', RoleController::class);

        // Rotas de Recurso para Usuários (CRUD) - AGORA TERÃO NOMES COMO 'admin.users.index', etc.
        Route::resource('users', UserController::class);

        // Rotas para Associação em Massa de Usuários a Grupos
        // Nomes agora são 'users.mass-assign-groups.index' e 'users.mass-assign-groups.store'
        // O prefixo 'admin.' será adicionado automaticamente pelo grupo.
        Route::get('/users/mass-assign-groups', [UserGroupAssignmentController::class, 'index'])
            ->name('users.mass-assign-groups.index');
        Route::post('/users/mass-assign-groups', [UserGroupAssignmentController::class, 'store'])
            ->name('users.mass-assign-groups.store');

        // Rotas para Associação em Massa de Papéis a Usuários
        // Essas rotas estavam comentadas. Assumindo que você quer ativá-las para o CRUD de Usuários.
        Route::get('/users/mass-assign-roles', [UserController::class, 'massAssignRolesIndex'])
            ->name('users.mass-assign-roles.index');
        Route::post('/users/mass-assign-roles', [UserController::class, 'massAssignRolesStore'])
            ->name('users.mass-assign-roles.store');
    });
});
