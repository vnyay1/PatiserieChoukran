<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livreur_zone_livraisons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('livreur_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('zone_livraison_id')
                ->constrained('zone_livraisons')
                ->cascadeOnDelete();

            $table->decimal('tarif_livraison', 10, 2);
            $table->integer('delai_livraison_min'); // en heures
            $table->integer('delai_livraison_max'); // en heures

            $table->timestamps();

            $table->unique(['livreur_id', 'zone_livraison_id'], 'livreur_zone_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livreur_zone_livraisons');
    }
};

