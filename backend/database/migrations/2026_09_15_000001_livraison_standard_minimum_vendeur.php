<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Livraison à prix unique : les frais ne dépendent plus du vendeur ni du quartier
 * (paramètre « frais_livraison_standard », 1500 FCFA). Chaque vendeur fixe en
 * revanche le montant d'achat minimum à partir duquel il livre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('montant_minimum_livraison', 10, 2)->default(0)->after('statut');
        });

        Schema::table('vendeur_tarifs_livraison', function (Blueprint $table) {
            $table->dropColumn('tarif');
        });

        DB::table('parametre_sites')->insertOrIgnore([
            'cle' => 'frais_livraison_standard',
            'valeur' => '1500',
            'type' => 'integer',
            'description' => 'Frais de livraison (FCFA) appliqués à chaque commande livrée, quel que soit le vendeur.',
            'groupe' => 'livraison',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('parametre_sites')->where('cle', 'frais_livraison_standard')->delete();

        Schema::table('vendeur_tarifs_livraison', function (Blueprint $table) {
            $table->decimal('tarif', 10, 2)->default(1000)->after('quartier_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('montant_minimum_livraison');
        });
    }
};
