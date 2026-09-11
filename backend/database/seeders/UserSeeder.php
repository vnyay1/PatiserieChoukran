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

        // Vendeurs test (deux vendeurs pour tester le panier multi-vendeur)
        User::create([
            'nom_complet' => 'Jean Vendeur',
            'telephone' => '+237699000002',
            'mot_de_passe' => 'password123',
            'role' => 'vendeur',
            'statut' => 'actif',
        ]);

        User::create([
            'nom_complet' => 'Awa Vendeuse',
            'telephone' => '+237699000004',
            'mot_de_passe' => 'password123',
            'role' => 'vendeur',
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
