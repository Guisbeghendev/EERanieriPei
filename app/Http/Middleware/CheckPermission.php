<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Lida com uma requisição HTTP.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string $permissionType   // Indica se é uma 'gate' ou 'policy'
     * @param  string $permissionName   // O nome da gate (ex: 'admin-only') ou da ação da policy (ex: 'update')
     * @param  string|null $modelParameter // Opcional: o nome do parâmetro da rota que representa o modelo (ex: 'gallery', 'user')
     */
    public function handle(Request $request, Closure $next, string $permissionType, string $permissionName, ?string $modelParameter = null): Response
    {
        if (! Auth::check()) {
            // Se o usuário não estiver logado, redireciona para a página de login.
            return redirect()->route('login');
        }

        $user = Auth::user();

        // INÍCIO DA CORREÇÃO APLICADA AQUI
        // Para a permissão 'admin-only' via Gate, verificamos diretamente o hasRole('admin').
        // Isso contorna o comportamento anômalo da Gate::allows() que estava retornando FALSE
        // mesmo quando hasRole('admin') era TRUE.
        if ($permissionType === 'gate' && $permissionName === 'admin-only') {
            if (! $user->hasRole('admin')) {
                abort(403, 'Você não tem permissão para acessar esta funcionalidade.');
            }
        }
        // Lógica original para outras Gates (que não 'admin-only')
        elseif ($permissionType === 'gate') {
            if (! Gate::allows($permissionName, $user)) {
                abort(403, 'Você não tem permissão para acessar esta funcionalidade.');
            }
        }
        // FIM DA CORREÇÃO APLICADA AQUI
        // A lógica para Policies permanece inalterada.
        elseif ($permissionType === 'policy') {
            // Se for para verificar uma Policy
            $modelInstance = null;
            // Se um parâmetro de modelo for fornecido (ex: para update em uma galeria específica)
            if ($modelParameter && $request->route($modelParameter)) {
                $modelInstance = $request->route($modelParameter);
            }

            // Chama a Policy: Gate::allows('update', $gallery) ou Gate::allows('create', App\Models\Gallery::class)
            // Se nenhum modelo for fornecido para uma política (ex: para 'create'), passa o próprio usuário.
            if (! Gate::allows($permissionName, $modelInstance ?? $user)) {
                abort(403, 'Você não tem permissão para realizar esta ação.');
            }
        } else {
            // Caso o tipo de permissão seja inválido.
            abort(500, 'Configuração de permissão inválida.');
        }

        return $next($request);
    }
}
