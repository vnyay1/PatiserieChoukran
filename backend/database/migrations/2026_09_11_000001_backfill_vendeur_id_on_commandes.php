<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Commandes antérieures au panier multi-vendeur : le vendeur n'était enregistré
     * que dans livreur_id. On le recopie dans vendeur_id pour que les listes vendeur,
     * le tableau de bord et les notifications s'appuient sur une seule colonne.
     */
    public function up(): void
    {
        DB::table('commandes')
            ->whereNull('vendeur_id')
            ->whereNotNull('livreur_id')
            ->update(['vendeur_id' => DB::raw('livreur_id')]);
    }

    public function down(): void
    {
        // Données seulement complétées : rien à annuler
    }
};
