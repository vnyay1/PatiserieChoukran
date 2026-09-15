<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProduitSeeder extends Seeder
{
    public function run(): void
    {
        $gateaux = Categorie::where('slug', 'gateaux')->first();
        $glaces = Categorie::where('slug', 'glaces')->first();
        $viennoiseries = Categorie::where('slug', 'viennoiseries')->first();

        // Vendeurs créés par UserSeeder : chaque produit doit appartenir à un vendeur
        // pour pouvoir être commandé (une commande par vendeur au checkout).
        $jean = User::where('telephone', '+237699000002')->first();
        $awa = User::where('telephone', '+237699000004')->first();

        $produits = [
            // Gâteaux
            [
                'categorie_id' => $gateaux->id,
                'created_by_user_id' => $jean?->id,
                'nom' => 'Gâteau au Chocolat',
                'slug' => 'gateau-au-chocolat',
                'description' => 'Délicieux gâteau au chocolat noir avec ganache',
                'prix_unitaire' => 15000,
                'image_principale' => null, // pas d'image fournie : le frontend affiche le placeholder
                'stock_disponible' => 10,
                'est_disponible' => true,
            ],
            [
                'categorie_id' => $gateaux->id,
                'created_by_user_id' => $jean?->id,
                'nom' => 'Gâteau Vanille-Fraise',
                'slug' => 'gateau-vanille-fraise',
                'description' => 'Gâteau à la vanille avec fraises fraîches',
                'prix_unitaire' => 12000,
                'prix_promo' => 10000,
                'image_principale' => null, // pas d'image fournie : le frontend affiche le placeholder
                'stock_disponible' => 8,
                'est_disponible' => true,
            ],
            // Glaces
            [
                'categorie_id' => $glaces->id,
                'created_by_user_id' => $awa?->id,
                'nom' => 'Coupe Glacée 3 Boules',
                'slug' => 'coupe-glacee-3-boules',
                'description' => 'Coupe avec 3 boules de glace au choix',
                'prix_unitaire' => 2000,
                'image_principale' => null, // pas d'image fournie : le frontend affiche le placeholder
                'stock_disponible' => 50,
                'est_disponible' => true,
            ],
            [
                'categorie_id' => $glaces->id,
                'created_by_user_id' => $awa?->id,
                'nom' => 'Cornet Simple',
                'slug' => 'cornet-simple',
                'description' => 'Cornet avec 1 boule de glace',
                'prix_unitaire' => 500,
                'image_principale' => null, // pas d'image fournie : le frontend affiche le placeholder
                'stock_disponible' => 100,
                'est_disponible' => true,
            ],
            // Viennoiseries
            [
                'categorie_id' => $viennoiseries->id,
                'created_by_user_id' => $jean?->id,
                'nom' => 'Croissant',
                'slug' => 'croissant',
                'description' => 'Croissant pur beurre',
                'prix_unitaire' => 500,
                'image_principale' => null, // pas d'image fournie : le frontend affiche le placeholder
                'stock_disponible' => 30,
                'est_disponible' => true,
            ],
            [
                'categorie_id' => $viennoiseries->id,
                'created_by_user_id' => $jean?->id,
                'nom' => 'Pain au Chocolat',
                'slug' => 'pain-au-chocolat',
                'description' => 'Pain au chocolat artisanal',
                'prix_unitaire' => 600,
                'image_principale' => null, // pas d'image fournie : le frontend affiche le placeholder
                'stock_disponible' => 25,
                'est_disponible' => true,
            ],
        ];

        foreach ($produits as $produit) {
            Produit::create($produit);
        }
    }
}
