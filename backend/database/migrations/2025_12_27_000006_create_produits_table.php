<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->constrained('categories')->onDelete('cascade');
            $table->string('nom');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('prix_unitaire', 10, 2);
            $table->decimal('prix_promo', 10, 2)->nullable();
            $table->string('image_principale')->nullable();
            $table->json('images_secondaires')->nullable();
            $table->integer('stock_disponible')->default(0);
            $table->boolean('est_disponible')->default(true);
            $table->boolean('est_vedette')->default(false);
            $table->integer('nombre_vues')->default(0);
            $table->integer('nombre_commandes')->default(0);
            $table->timestamps();

            $table->index(['categorie_id', 'est_disponible', 'est_vedette']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
