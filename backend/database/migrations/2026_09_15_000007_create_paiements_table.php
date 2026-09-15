<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paiements mobile money via NotchPay : un paiement couvre une ou plusieurs commandes
 * (celles créées ensemble au checkout, une par vendeur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            // Référence envoyée à NotchPay et renvoyée au retour / dans les webhooks
            $table->string('reference', 40)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->enum('moyen_paiement', ['orange_money', 'mtn_momo']);
            $table->string('telephone', 20)->nullable();
            $table->enum('statut', ['en_attente', 'complete', 'echec', 'annule', 'expire'])->default('en_attente');
            $table->string('notchpay_id', 100)->nullable();
            $table->timestamp('paye_le')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'statut']);
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->foreignId('paiement_id')->nullable()->after('reference_paiement')->constrained('paiements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paiement_id');
        });

        Schema::dropIfExists('paiements');
    }
};
