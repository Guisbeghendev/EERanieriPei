<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request; // Importar Request
use Illuminate\Support\Facades\Auth; // Importar Auth

class AdminDashboardController extends Controller
{
    /**
     * Exibe o dashboard do administrador.
     * Este método é chamado pela rota '/admin/dashboard' que espera `index()`.
     */
    public function index()
    {
        // Recupera o usuário autenticado para passar informações para a view.
        $user = Auth::user();

        return Inertia::render('Admin/Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                // Assumindo que o trait HasRoles no modelo User carrega a relação 'roles'.
                'roles' => $user->roles->pluck('name'),
            ],
            'message' => 'Bem-vindo ao Dashboard do Administrador!',
            // Adicione aqui quaisquer outros dados específicos que o dashboard do admin precise.
        ]);
    }
}
