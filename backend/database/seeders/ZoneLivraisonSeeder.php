<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ZoneLivraison;

class ZoneLivraisonSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            // Douala
            [
                'nom_zone' => 'Akwa',
                'ville' => 'Douala',
                'tarif_livraison' => 500,
                'delai_livraison_min' => 1,
                'delai_livraison_max' => 2,
                'est_active' => true,
            ],
            [
                'nom_zone' => 'Bonanjo',
                'ville' => 'Douala',
                'tarif_livraison' => 500,
                'delai_livraison_min' => 1,
                'delai_livraison_max' => 2,
                'est_active' => true,
            ],
            [
                'nom_zone' => 'Bonapriso',
                'ville' => 'Douala',
                'tarif_livraison' => 800,
                'delai_livraison_min' => 2,
                'delai_livraison_max' => 3,
                'est_active' => true,
            ],
            [
                'nom_zone' => 'Bepanda',
                'ville' => 'Douala',
                'tarif_livraison' => 1000,
                'delai_livraison_min' => 2,
                'delai_livraison_max' => 4,
                'est_active' => true,
            ],
            [
                'nom_zone' => 'Makepe',
                'ville' => 'Douala',
                'tarif_livraison' => 1500,
                'delai_livraison_min' => 3,
                'delai_livraison_max' => 5,
                'est_active' => true,
            ],
            // Yaoundé
            [
                'nom_zone' => 'Centre-ville',
                'ville' => 'Yaoundé',
                'tarif_livraison' => 1000,
                'delai_livraison_min' => 2,
                'delai_livraison_max' => 3,
                'est_active' => true,
            ],
            [
                'nom_zone' => 'Bastos',
                'ville' => 'Yaoundé',
                'tarif_livraison' => 1500,
                'delai_livraison_min' => 2,
                'delai_livraison_max' => 4,
                'est_active' => true,
            ],
        ];

        foreach ($zones as $zone) {
            ZoneLivraison::create($zone);
        }
    }
}