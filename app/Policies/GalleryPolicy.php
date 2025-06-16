<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Gallery;
use App\Models\Group; // Importar Group
use Illuminate\Auth\Access\HandlesAuthorization;

class GalleryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models (para listagem de gerenciamento de galerias).
     */
    public function viewAny(User $user): bool
    {
        // Apenas fotógrafos podem ver a lista de galerias para gerenciar.
        return $user->hasRole('fotografo');
    }

    /**
     * Determine whether the user can view a specific Gallery.
     * Esta é para acesso de usuários autenticados, fotógrafos e donos.
     * A visualização pública será via Gate 'view-public-gallery'.
     */
    public function view(User $user, Gallery $gallery): bool
    {
        // Fotógrafos sempre podem ver qualquer galeria.
        if ($user->hasRole('fotografo')) {
            return true;
        }

        // O dono da galeria (que será um fotógrafo) sempre pode ver sua própria galeria.
        // Já coberto pelo hasRole('fotografo'), mas deixado para clareza.
        if ($user->id === $gallery->user_id) {
            return true;
        }

        // Usuário pode ver se pertence a algum dos grupos da galeria.
        if (!$gallery->relationLoaded('groups')) {
            $gallery->load('groups');
        }
        if (!$user->relationLoaded('groups')) {
            $user->load('groups');
        }
        return $user->groups->pluck('id')->intersect($gallery->groups->pluck('id'))->isNotEmpty();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Apenas fotógrafos podem criar galerias.
        return $user->hasRole('fotografo');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Gallery $gallery): bool
    {
        // Apenas fotógrafos podem atualizar suas próprias galerias.
        return $user->hasRole('fotografo') && $user->id === $gallery->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Gallery $gallery): bool
    {
        // Apenas fotógrafos podem deletar suas próprias galerias.
        return $user->hasRole('fotografo') && $user->id === $gallery->user_id;
    }

    /**
     * Determine whether the user can manage a gallery (e.g., upload images, delete individual images).
     */
    public function manageGallery(User $user, Gallery $gallery): bool
    {
        // Apenas fotógrafos podem gerenciar as galerias que lhes pertencem.
        return $user->hasRole('fotografo') && $user->id === $gallery->user_id;
    }
}
