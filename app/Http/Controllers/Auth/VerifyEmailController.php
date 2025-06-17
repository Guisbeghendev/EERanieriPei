<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // Se o e-mail do usuário já estiver verificado, redireciona.
        if ($request->user()->hasVerifiedEmail()) {
            // AQUI: Alterado para URL literal.
            // Mantendo '?verified=1' para que o frontend possa exibir uma mensagem de sucesso.
            return redirect()->intended('/dashboard?verified=1');
        }

        // Tenta marcar o e-mail como verificado.
        if ($request->user()->markEmailAsVerified()) {
            // Dispara o evento 'Verified' se a verificação for bem-sucedida.
            event(new Verified($request->user()));
        }

        // Redireciona para o dashboard após a verificação (ou se já estiver verificado).
        // AQUI: Alterado para URL literal.
        return redirect()->intended('/dashboard?verified=1');
    }
}
