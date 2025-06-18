<?php

namespace App\Models\Traits;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    /**
     * Um usuário pode ter várias roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    /**
     * Verifica se o usuário possui uma determinada role.
     *
     * @param string|array $role
     * @return bool
     */
    public function hasRole(string|array $role): bool
    {
        // Garante que a relação 'roles' esteja carregada.
        if (!$this->relationLoaded('roles')) {
            $this->load('roles');
        }

        // Se $role for um array, verifica se o usuário tem ALGUMA das roles.
        if (is_array($role)) {
            foreach ($role as $r) {
                if ($this->roles->contains('name', $r)) {
                    return true;
                }
            }
            return false;
        }

        // Se $role for uma string, verifica se o usuário tem ESSA role específica.
        return $this->roles->contains('name', $role);
    }
}
