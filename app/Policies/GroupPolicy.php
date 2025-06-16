<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Group;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any groups (list groups).
     */
    public function viewAny(User $user): bool
    {
        return true; // Qualquer usuário autenticado pode ver a lista de grupos.
    }

    /**
     * Determine whether the user can view a specific group.
     */
    public function view(User $user, Group $group): bool
    {
        return true; // Qualquer usuário autenticado pode ver um grupo específico.
    }

    /**
     * Determine whether the user can create groups.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the group.
     */
    public function update(User $user, Group $group): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the group.
     */
    public function delete(User $user, Group $group): bool
    {
        return $user->hasRole('admin');
    }
}
