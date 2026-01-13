<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_commande', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('montant_produits', 10, 2);
            $table->decimal('montant_livraison', 10, 2)->default(0);
            $table->decimal('montant_total', 10, 2);
            $table->string('devise', 10)->default('XAF');
            $table->enum('statut', [
                'en_attente',
                'confirmee',
                'en_preparation',
                'prete',
                'en_livraison',
                'livree',
                'annulee'
            ])->default('en_attente');
            $table->enum('statut_paiement', [
                'en_attente',
                'paye',
                'echec',
                'rembourse'
            ])->default('en_attente');
            $table->enum('type_livraison', ['livraison', 'retrait_boutique']);
            $table->foreignId('adresse_livraison_id')->nullable()->constrained('adresses')->onDelete('set null');
            $table->string('telephone_livraison', 20)->nullable();
            $table->date('date_livraison_souhaitee')->nullable();
            $table->time('heure_livraison_souhaitee')->nullable();
            $table->text('instructions_speciales')->nullable();
            $table->enum('moyen_paiement', ['orange_money', 'mtn_momo', 'especes']);
            $table->enum('operateur_mobile', ['orange', 'mtn'])->nullable();
            $table->string('telephone_paiement', 20)->nullable();
            $table->string('reference_paiement')->nullable();
            $table->timestamp('date_paiement')->nullable();
            $table->foreignId('livreur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['user_id', 'statut', 'created_at']);
            $table->index('statut_paiement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
