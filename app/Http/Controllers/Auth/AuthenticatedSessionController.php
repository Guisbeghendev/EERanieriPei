<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route; // Mantido para 'canResetPassword' que é uma flag
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        // Embora 'password.request' seja um nome de rota do Laravel,
        // a flag 'canResetPassword' é apenas um booleano enviado para o frontend.
        // O frontend usa uma URL literal '/forgot-password'.
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // AQUI: Alterado para uma URL literal ou uma rota nomeada simples se o Ziggy não for usado no frontend para redirecionamento.
        // Se 'dashboard' é um nome de rota, e o frontend precisa dessa informação para redirecionar,
        // o Laravel ainda pode gerar a URL para ele, mas certifique-se que o componente Vue está
        // preparado para receber e usar essa URL, ou redirecione para uma URL literal.
        // Para simplificar e evitar qualquer dependência, redirecionaremos para a URL literal.
        // return redirect()->intended(route('dashboard', absolute: false)); // Versão original
        return redirect()->intended('/dashboard'); // Versão ajustada para URL literal
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // AQUI: Já estava redirecionando para a URL literal '/', o que está perfeito.
        return redirect('/');
    }

    // --- Métodos para Confirmação de Senha (Adicionados para completude do fluxo de autenticação) ---

    /**
     * Display the password confirmation view.
     */
    public function confirmPassword(): Response
    {
        return Inertia::render('Auth/ConfirmPassword');
    }

    /**
     * Confirm the user's password.
     */
    public function storeConfirmedPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $request->session()->passwordConfirmed();

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
