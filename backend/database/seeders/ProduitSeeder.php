<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produit;
use App\Models\Categorie;

class ProduitSeeder extends Seeder
{
    public function run(): void
    {
        $gateaux = Categorie::where('slug', 'gateaux')->first();
        $glaces = Categorie::where('slug', 'glaces')->first();
        $viennoiseries = Categorie::where('slug', 'viennoiseries')->first();

        $produits = [
            // Gâteaux
            [
                'categorie_id' => $gateaux->id,
                'nom' => 'Gâteau au Chocolat',
                'slug' => 'gateau-au-chocolat',
                'description' => 'Délicieux gâteau au chocolat noir avec ganache',
                'prix_unitaire' => 15000,
                'image_principale' => '/images/produits/gateau-chocolat.jpg',
                'stock_disponible' => 10,
                'est_disponible' => true,
                'est_vedette' => true,
            ],
            [
                'categorie_id' => $gateaux->id,
                'nom' => 'Gâteau Vanille-Fraise',
                'slug' => 'gateau-vanille-fraise',
                'description' => 'Gâteau à la vanille avec fraises fraîches',
                'prix_unitaire' => 12000,
                'prix_promo' => 10000,
                'image_principale' => '/images/produits/gateau-fraise.jpg',
                'stock_disponible' => 8,
                'est_disponible' => true,
                'est_vedette' => false,
            ],
            // Glaces
            [
                'categorie_id' => $glaces->id,
                'nom' => 'Coupe Glacée 3 Boules',
                'slug' => 'coupe-glacee-3-boules',
                'description' => 'Coupe avec 3 boules de glace au choix',
                'prix_unitaire' => 2000,
                'image_principale' => '/images/produits/coupe-glace.jpg',
                'stock_disponible' => 50,
                'est_disponible' => true,
                'est_vedette' => true,
            ],
            [
                'categorie_id' => $glaces->id,
                'nom' => 'Cornet Simple',
                'slug' => 'cornet-simple',
                'description' => 'Cornet avec 1 boule de glace',
                'prix_unitaire' => 500,
                'image_principale' => '/images/produits/cornet.jpg',
                'stock_disponible' => 100,
                'est_disponible' => true,
                'est_vedette' => false,
            ],
            // Viennoiseries
            [
                'categorie_id' => $viennoiseries->id,
                'nom' => 'Croissant',
                'slug' => 'croissant',
                'description' => 'Croissant pur beurre',
                'prix_unitaire' => 500,
                'image_principale' => '/images/produits/croissant.jpg',
                'stock_disponible' => 30,
                'est_disponible' => true,
                'est_vedette' => false,
            ],
            [
                'categorie_id' => $viennoiseries->id,
                'nom' => 'Pain au Chocolat',
                'slug' => 'pain-au-chocolat',
                'description' => 'Pain au chocolat artisanal',
                'prix_unitaire' => 600,
                'image_principale' => '/images/produits/pain-chocolat.jpg',
                'stock_disponible' => 25,
                'est_disponible' => true,
                'est_vedette' => false,
            ],
        ];

        foreach ($produits as $produit) {
            Produit::create($produit);
        }
    }
}