<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le client ne choisit plus de date ni d'heure de livraison : le vendeur livre
 * dès que la commande est prête et le contacte au téléphone indiqué.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['date_livraison_souhaitee', 'heure_livraison_souhaitee']);
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->date('date_livraison_souhaitee')->nullable();
            $table->time('heure_livraison_souhaitee')->nullable();
        });
    }
};
