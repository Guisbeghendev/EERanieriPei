<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): Response
    {
        // Esta parte está perfeita. Ela passa o email e o token para o frontend,
        // que o componente 'Auth/ResetPassword' usará para pré-preencher campos.
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // A lógica de resetar a senha via `Password::reset` está correta.
        // O callback anônimo atualiza a senha do usuário e dispara o evento PasswordReset.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // AQUI: Este é o único ponto de ajuste.
        // O original `redirect()->route('login')` usa uma rota nomeada.
        // Para manter a consistência com a abordagem "sem Ziggy",
        // vamos redirecionar para a URL literal de login.
        if ($status == Password::PASSWORD_RESET) {
            return redirect('/login')->with('status', __($status)); // Alterado para URL literal
        }

        // Se o reset falhar, lança uma exceção de validação.
        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
