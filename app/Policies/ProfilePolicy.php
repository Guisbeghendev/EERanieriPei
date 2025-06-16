<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the profile.
     */
    public function view(User $user, Profile $profile): bool
    {
        return true; // Perfis são públicos para visualização (assumindo para usuários autenticados).
    }

    /**
     * Determine whether the user can update the profile.
     */
    public function update(User $user, Profile $profile): bool
    {
        // Admin pode atualizar qualquer perfil. Usuário pode atualizar o próprio perfil.
        return $user->hasRole('admin') || $user->id === $profile->user_id;
    }
}
