<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ZoneLivraison;

class ZoneLivraisonSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [];

        // Zones de Yaoundé depuis le fichier quartiersYaounde.txt (backend/database/data/)
        $yaoundeFilePath = database_path('data/quartiersYaounde.txt');
        if (file_exists($yaoundeFilePath)) {
            $yaoundeZones = file($yaoundeFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

            foreach ($yaoundeZones as $zoneName) {
                $zoneName = trim($zoneName);

                if ($zoneName === '') {
                    continue;
                }

                $zones[] = [
                    'nom_zone' => $zoneName,
                    'ville' => 'Yaoundé',
                    'tarif_livraison' => 1000,
                    'delai_livraison_min' => 0,
                    'delai_livraison_max' => 0,
                    'est_active' => true,
                ];
            }
        }

        // Zones de Douala depuis le fichier quartiersDouala.txt (backend/database/data/)
        $doualaFilePath = database_path('data/quartiersDouala.txt');
        if (file_exists($doualaFilePath)) {
            $doualaZones = file($doualaFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

            foreach ($doualaZones as $zoneName) {
                $zoneName = trim($zoneName);

                if ($zoneName === '') {
                    continue;
                }

                $zones[] = [
                    'nom_zone' => $zoneName,
                    'ville' => 'Douala',
                    'tarif_livraison' => 1000,
                    'delai_livraison_min' => 0,
                    'delai_livraison_max' => 0,
                    'est_active' => true,
                ];
            }
        }

        foreach ($zones as $zone) {
            ZoneLivraison::create($zone);
        }
    }
}