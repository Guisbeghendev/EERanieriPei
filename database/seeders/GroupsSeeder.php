<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
// Não precisa mais importar App\Models\Role aqui, pois não buscaremos os nomes dinamicamente.

class GroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lista de nomes de grupos, iguais aos nomes das roles.
        // A ordem aqui não importa para a criação, mas é bom para leitura.
        $groupNames = [
            'público',      // Grupo padrão que será atribuído a novos usuários.
            'admin',
            'fotografo',
            'família',
            'aluno',
            'professor',
            'funcionário',
            'gestão',
            'DE',
        ];

        // Cria cada grupo se ainda não existir.
        foreach ($groupNames as $groupName) {
            Group::firstOrCreate(
                ['name' => $groupName],
                [
                    // Você pode personalizar a descrição para cada grupo se quiser.
                    // Para simplificar, usamos uma descrição genérica.
                    'description' => "Grupo de acesso para '{$groupName}'.",
                ]
            );
        }

        // Lembrete: O UserObserver é responsável por associar o grupo 'público' a todo novo usuário cadastrado.
        // Isso NÃO é feito neste Seeder, mas sim pelo Observer.
    }
}
