<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->unique()->constrained('commandes')->cascadeOnDelete();
            $table->string('numero_facture', 30)->unique();
            // Chemin du PDF sur le disque « local » (privé)
            $table->string('fichier');
            $table->decimal('montant_total', 10, 2);
            $table->string('email_destinataire')->nullable();
            $table->timestamp('envoyee_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
