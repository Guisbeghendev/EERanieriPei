<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        // Esta parte está correta. O componente 'Auth/ForgotPassword' renderiza a view,
        // e o 'status' da sessão é passado para exibir mensagens.
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // AQUI: Esta parte do método já está perfeita.
        // O helper `Password::sendResetLink` lida com o envio do e-mail.
        // E o `back()->with('status', ...)` redireciona para a página anterior
        // (que é a de 'Esqueci a Senha') com a mensagem de status na sessão.
        // Isso não usa rotas nomeadas diretamente para o redirecionamento,
        // então não há conflito com a abordagem "sem Ziggy".
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
