<?php

namespace Database\Seeders;

use App\Models\Quartier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendeurVilleSeeder extends Seeder
{
    /**
     * Villes livrées par les vendeurs de démonstration : Jean à Yaoundé, Awa à Douala
     * (pour voir le catalogue changer selon la ville choisie), les autres partout.
     * insertOrIgnore : les villes déjà choisies par un vendeur sont conservées.
     */
    public function run(): void
    {
        $villesParTelephone = [
            '+237699000002' => ['yaoundé'],
            '+237699000004' => ['douala'],
        ];

        User::vendeurs()->get(['id', 'telephone'])->each(function (User $vendeur) use ($villesParTelephone) {
            $villes = $villesParTelephone[$vendeur->telephone] ?? array_keys(Quartier::VILLES);

            DB::table('vendeur_villes')->insertOrIgnore(array_map(fn (string $ville) => [
                'vendeur_id' => $vendeur->id,
                'ville' => $ville,
                'created_at' => now(),
                'updated_at' => now(),
            ], $villes));
        });
    }
}
