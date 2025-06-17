<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User; // Importe o modelo User
use App\Models\Group; // Importe o modelo Group
use App\Models\Gallery; // Importe o modelo Gallery
use Illuminate\Support\Facades\Auth; // Para acessar o usuário autenticado

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function __invoke(Request $request)
    {
        // 1. Obter o usuário autenticado
        $user = Auth::user();

        // 2. Carregar os grupos do usuário com as últimas galerias
        // Aqui, precisamos garantir que seus modelos e relacionamentos estejam configurados:
        // - Um relacionamento "belongsToMany" entre User e Group (via tabela pivot 'group_user').
        // - Um relacionamento "hasMany" de Group para Gallery.
        // - As galerias serão ordenadas pela data do evento ou criação e limitadas.

        // Exemplo de como carregar os dados (assumindo os relacionamentos):
        $userGroupsWithLatestGalleries = [];

        if ($user) {
            $userGroupsWithLatestGalleries = $user->groups()->with(['galleries' => function ($query) {
                $query->orderBy('event_date', 'desc') // Ou 'created_at', dependendo do que você quer como "últimas"
                ->take(6); // Limita o número de galerias por grupo para não sobrecarregar
            }])->get();

            // Mapper para formatar os dados como esperado pelo frontend e adicionar apenas galerias com imagens
            $userGroupsWithLatestGalleries = $userGroupsWithLatestGalleries->map(function ($group) {
                // Filtra galerias para incluir apenas as que possuem imagens
                $galleriesWithImages = $group->galleries->filter(function ($gallery) {
                    return $gallery->images->isNotEmpty(); // Assumindo que Gallery tem um hasMany 'images'
                });

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'galleries' => $galleriesWithImages->map(function ($gallery) {
                        return [
                            'id' => $gallery->id,
                            'title' => $gallery->title,
                            'event_date' => $gallery->event_date,
                            // Carrega a primeira imagem para o getFirstImageUrl no frontend
                            'images' => $gallery->images->map(function ($image) {
                                return [
                                    'path_original' => $image->path_original,
                                ];
                            })->take(1)->toArray(), // Pega apenas a primeira imagem
                        ];
                    })->values()->all(), // Garante que as chaves do array sejam resetadas
                ];
            });
        }


        // 3. Retornar a view Inertia com as props
        return Inertia::render('Dashboard', [
            // A prop 'auth' já é injetada automaticamente pelo Inertia quando você usa o Breeze
            // e o middleware HandleInertiaRequests configura 'auth.user'.
            'userGroupsWithLatestGalleries' => $userGroupsWithLatestGalleries,
        ]);
    }
}
