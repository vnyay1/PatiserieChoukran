<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuartierSeeder extends Seeder
{
    public function run(): void
    {
        $quartiers = array_merge(
            $this->quartiersYaounde(),
            $this->quartiersDouala(),
            $this->quartiersFromFile('data/quartiersYaounde.txt', 'yaoundé'),
            $this->quartiersFromFile('data/quartiersDouala.txt', 'douala'),
        );

        $now = now();
        $unique = [];

        foreach ($quartiers as $quartier) {
            $nom = trim($quartier['nom']);
            $ville = $quartier['ville'];

            if ($nom === '') {
                continue;
            }

            $key = mb_strtolower($nom.'|'.$ville);
            $unique[$key] = [
                'nom' => $nom,
                'ville' => $ville,
                'actif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('quartiers')->upsert(
            array_values($unique),
            ['nom', 'ville'],
            ['actif', 'updated_at']
        );
    }

    private function quartiersYaounde(): array
    {
        return array_map(fn (string $nom) => ['nom' => $nom, 'ville' => 'yaoundé'], [
            'Bastos',
            'Centre-ville',
            'Melen',
            'Nlongkak',
            'Mvog-Mbi',
            'Essos',
            'Omnisports',
            'Biyem-Assi',
            'Mendong',
            'Ngousso',
            'Ekounou',
            'Nkolbisson',
            'Mfandena',
            'Elig-Edzoa',
            'Oyom-Abang',
            'Mimboman',
            'Simbock',
            'Nkol-Eton',
            'Nsimeyong',
            'Tsinga',
        ]);
    }

    private function quartiersDouala(): array
    {
        return array_map(fn (string $nom) => ['nom' => $nom, 'ville' => 'douala'], [
            'Akwa',
            'Bonanjo',
            'Deido',
            'Bonabéri',
            'Makepe',
            'Logpom',
            'Ndokoti',
            'Bepanda',
            'New-Bell',
            'Nkoulouloun',
            'Nyalla',
            'Kotto',
            'Bonapriso',
            'Bonamoussadi',
            'PK8-14',
        ]);
    }

    private function quartiersFromFile(string $relativePath, string $ville): array
    {
        $path = database_path($relativePath);

        if (!file_exists($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        return array_map(
            fn (string $nom) => ['nom' => trim($nom), 'ville' => $ville],
            $lines
        );
    }
}
