<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'nom' => 'Gâteaux',
                'slug' => 'gateaux',
                'description' => 'Nos délicieux gâteaux pour toutes les occasions',
                'image' => '/images/categories/gateaux.jpg',
                'ordre_affichage' => 1,
                'est_actif' => true,
            ],
            [
                'nom' => 'Glaces',
                'slug' => 'glaces',
                'description' => 'Glaces artisanales et cornets',
                'image' => '/images/categories/glaces.jpg',
                'ordre_affichage' => 2,
                'est_actif' => true,
            ],
            [
                'nom' => 'Viennoiseries',
                'slug' => 'viennoiseries',
                'description' => 'Croissants, pains au chocolat et plus',
                'image' => '/images/categories/viennoiseries.jpg',
                'ordre_affichage' => 3,
                'est_actif' => true,
            ],
            [
                'nom' => 'Pâtisseries',
                'slug' => 'patisseries',
                'description' => 'Éclairs, tartes, macarons...',
                'image' => '/images/categories/patisseries.jpg',
                'ordre_affichage' => 4,
                'est_actif' => true,
            ],
            [
                'nom' => 'Anniversaires',
                'slug' => 'anniversaires',
                'description' => 'Gâteaux d\'anniversaire sur mesure',
                'image' => '/images/categories/anniversaires.jpg',
                'ordre_affichage' => 5,
                'est_actif' => true,
            ],
        ];

        foreach ($categories as $categorie) {
            Categorie::create($categorie);
        }
    }
}
