<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin principal
        User::create([
            'nom_complet' => 'Administrateur',
            'email' => 'admin@patisserie.cm',
            'telephone' => '+237699000001',
            'mot_de_passe' => 'password123',
            'role' => 'admin',
            'statut' => 'actif',
            'email_verified_at' => now(),
        ]);

        // Livreur test
        User::create([
            'nom_complet' => 'Jean Livreur',
            'telephone' => '+237699000002',
            'mot_de_passe' => 'password123',
            'role' => 'livreur',
            'statut' => 'actif',
        ]);

        // Client test
        User::create([
            'nom_complet' => 'Marie Cliente',
            'email' => 'marie@email.cm',
            'telephone' => '+237699000003',
            'mot_de_passe' => 'password123',
            'role' => 'client',
            'statut' => 'actif',
        ]);
    }
}