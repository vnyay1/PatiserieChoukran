<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Une seule ligne de panier par client et par produit : deux ajouts simultanés
 * créaient deux lignes, et le checkout (contrôle du stock ligne par ligne) pouvait
 * alors vendre plus que le stock. Les doublons existants sont fusionnés sur la
 * plus ancienne ligne, quantités additionnées.
 */
return new class extends Migration
{
    public function up(): void
    {
        $doublons = DB::table('paniers')
            ->select('user_id', 'produit_id')
            ->groupBy('user_id', 'produit_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($doublons as $doublon) {
            $lignes = DB::table('paniers')
                ->where('user_id', $doublon->user_id)
                ->where('produit_id', $doublon->produit_id)
                ->orderBy('id')
                ->get();

            $gardee = $lignes->first();
            $quantite = (int) $lignes->sum('quantite');

            DB::table('paniers')->where('id', $gardee->id)->update([
                'quantite' => $quantite,
                'sous_total' => $quantite * (float) $gardee->prix_unitaire_actuel,
            ]);
            DB::table('paniers')->whereIn('id', $lignes->skip(1)->pluck('id'))->delete();
        }

        Schema::table('paniers', function (Blueprint $table) {
            $table->unique(['user_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        // MySQL : la clé étrangère user_id reste servie par l'index (user_id, updated_at)
        Schema::table('paniers', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'produit_id']);
        });
    }
};
