<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendeur_tarifs_livraison', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendeur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('quartier_id')->constrained('quartiers')->cascadeOnDelete();
            $table->decimal('tarif', 10, 2);
            $table->unsignedSmallInteger('delai_min')->default(30);
            $table->unsignedSmallInteger('delai_max')->default(60);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['vendeur_id', 'quartier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendeur_tarifs_livraison');
    }
};
