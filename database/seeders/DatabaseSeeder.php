<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,    // Primeiro: cria todas as roles
            GroupsSeeder::class,   // Segundo: cria todos os grupos (depende das roles)
            ExampleUserSeeder::class,
            AdminUserSeeder::class,
            FotografoUserSeeder::class,
            // Adicione outros seeders de dados aqui, se houver
        ]);
    }
}
