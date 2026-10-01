<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adresse de la page de paiement NotchPay : « Payer maintenant » rouvre le paiement
 * déjà ouvert au lieu d'en créer un second (le client aurait pu payer deux fois).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->string('url_paiement', 2048)->nullable()->after('notchpay_id');
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropColumn('url_paiement');
        });
    }
};
