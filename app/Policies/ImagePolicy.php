<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Image;
use App\Models\Gallery;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Image $image): bool
    {
        $gallery = $image->gallery; // Carrega a galeria associada à imagem

        // Fotógrafos sempre podem ver qualquer imagem.
        if ($user->hasRole('fotografo')) {
            return true;
        }

        // O dono da galeria da imagem (que será um fotógrafo) sempre pode ver.
        // Já coberto pelo hasRole('fotografo'), mas deixado para clareza.
        if ($user->id === $gallery->user_id) {
            return true;
        }

        // Usuário pode ver se pertence a algum dos grupos da galeria da imagem.
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
    public function create(User $user, Gallery $gallery): bool
    {
        // Apenas fotógrafos podem criar imagens em galerias que lhes pertencem.
        return $user->hasRole('fotografo') && $user->id === $gallery->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Image $image): bool
    {
        $gallery = $image->gallery; // Carrega a galeria associada à imagem
        // Apenas fotógrafos podem atualizar imagens em galerias que lhes pertencem.
        return $user->hasRole('fotografo') && $user->id === $gallery->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Image $image): bool
    {
        $gallery = $image->gallery; // Carrega a galeria associada à imagem
        // Apenas fotógrafos podem deletar imagens em galerias que lhes pertencem.
        return $user->hasRole('fotografo') && $user->id === $gallery->user_id;
    }
}
