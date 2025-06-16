<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lista de roles (funções dos usuários)
        $roles = [
            'admin',        // gestão total de users
            'fotografo',    // gestão de galerias e imagens
            'público',      // usuário geral, visitante autenticado
            'família',      // Exemplo de role de acesso (se roles também forem para acesso)
            'aluno',        // Exemplo de role
            'professor',    // Exemplo de role
            'funcionário',  // Exemplo de role
            'gestão',       // Exemplo de role
            'DE',           // Exemplo de role
        ];

        // Cria cada role se ainda não existir
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }
    }
}
