<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->foreignId('produit_id')->constrained('produits')->onDelete('cascade');
            $table->string('nom_produit');
            $table->integer('quantite')->default(1);
            $table->decimal('prix_unitaire', 10, 2);
            $table->decimal('sous_total', 10, 2);
            $table->timestamps();
            
            $table->index('commande_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_commandes');
    }
};