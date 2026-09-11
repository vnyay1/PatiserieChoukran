<?php

namespace Database\Seeders;

use App\Models\Quartier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendeurTarifLivraisonSeeder extends Seeder
{
    /**
     * Tarif par défaut (1000 FCFA, 30-60 min) pour chaque vendeur sur chaque quartier actif.
     * insertOrIgnore s'appuie sur l'index unique (vendeur_id, quartier_id) :
     * un tarif déjà défini par un vendeur n'est jamais écrasé.
     */
    public function run(): void
    {
        $quartierIds = Quartier::where('actif', true)->pluck('id');
        $now = now();

        User::vendeurs()->pluck('id')->each(function (int $vendeurId) use ($quartierIds, $now) {
            $quartierIds->chunk(200)->each(function ($chunk) use ($vendeurId, $now) {
                DB::table('vendeur_tarifs_livraison')->insertOrIgnore(
                    $chunk->map(fn (int $quartierId) => [
                        'vendeur_id' => $vendeurId,
                        'quartier_id' => $quartierId,
                        'tarif' => 1000,
                        'delai_min' => 30,
                        'delai_max' => 60,
                        'actif' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->values()->all()
                );
            });
        });
    }
}
