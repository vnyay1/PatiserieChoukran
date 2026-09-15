<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            QuartierSeeder::class,
            CategorieSeeder::class,
            ProduitSeeder::class,
            VendeurVilleSeeder::class,
        ]);
    }
}
