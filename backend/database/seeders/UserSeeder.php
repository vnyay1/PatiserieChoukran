<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

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

        // Vendeurs test (deux vendeurs pour tester le panier multi-vendeur), avec un
        // profil boutique complet : sans lui, leurs produits seraient masqués.
        User::create([
            'nom_complet' => 'Jean Vendeur',
            'email' => 'jean@choukrane.test',
            'telephone' => '+237699000002',
            'mot_de_passe' => 'password123',
            'role' => 'vendeur',
            'statut' => 'actif',
            'logo_boutique' => $this->copierLogo('logo-jean.svg'),
            'description_boutique' => 'Pâtissier à Yaoundé depuis quinze ans : gâteaux d\'anniversaire, entremets au chocolat et fraisiers préparés chaque matin.',
            'conditions_acceptees_le' => now(),
            'montant_minimum_livraison' => 5000,
            'est_vendeur_vedette' => true,
        ]);

        User::create([
            'nom_complet' => 'Awa Vendeuse',
            'email' => 'awa@choukrane.test',
            'telephone' => '+237699000004',
            'mot_de_passe' => 'password123',
            'role' => 'vendeur',
            'statut' => 'actif',
            'logo_boutique' => $this->copierLogo('logo-awa.svg'),
            'description_boutique' => 'Glaces artisanales et viennoiseries à Douala : parfums de saison (mangue, corossol, bissap) et croissants pur beurre.',
            'conditions_acceptees_le' => now(),
            'montant_minimum_livraison' => 3000,
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

    // Logo de démonstration copié sur le disque public (servi par /storage)
    private function copierLogo(string $fichier): string
    {
        $chemin = 'boutiques/'.$fichier;
        Storage::disk('public')->put($chemin, file_get_contents(__DIR__.'/fichiers/'.$fichier));

        return $chemin;
    }
}
