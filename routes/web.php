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
use App\Http\Controllers\Admin\GroupController; // NOVO: Importa o GroupController
use App\Http\Controllers\Admin\UserGroupAssignmentController; // FUTURO: Para a associação em massa de grupos

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
    // Protegidas com o middleware 'check.permission' e a gate 'admin-only'
    Route::prefix('admin')->middleware(['check.permission:gate,admin-only'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('admin.dashboard');

        // Rotas de Recurso para Grupos (CRUD)
        // Isso criará rotas como /admin/groups, /admin/groups/create, /admin/groups/{group}/edit, etc.
        Route::resource('groups', GroupController::class);

        // FUTURAS Rotas para Associação em Massa de Usuários a Grupos
        // Você precisará de um UserGroupAssignmentController para isso.
        // Route::get('/users/mass-assign-groups', [UserGroupAssignmentController::class, 'index']); // GET para a página de seleção/filtro
        // Route::post('/users/mass-assign-groups', [UserGroupAssignmentController::class, 'store']); // POST para salvar a associação

        // FUTURAS Rotas para Gerenciamento de Papéis (CRUD)
        // Route::resource('roles', AdminRoleController::class);

        // FUTURAS Rotas para Gerenciamento de Usuários (CRUD)
        // Route::resource('users', AdminUserController::class);

        // FUTURAS Rotas para Associação em Massa de Papéis a Usuários
        // Route::get('/users/mass-assign-roles', [UserRoleAssignmentController::class, 'index']);
        // Route::post('/users/mass-assign-roles', [UserRoleAssignmentController::class, 'store']);
    });
});
