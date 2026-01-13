<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zone_livraisons', function (Blueprint $table) {
            $table->id();
            $table->string('nom_zone');
            $table->string('ville');
            $table->decimal('tarif_livraison', 10, 2);
            $table->integer('delai_livraison_min'); // en heures
            $table->integer('delai_livraison_max'); // en heures
            $table->boolean('est_active')->default(true);
            $table->timestamps();
            
            $table->index(['ville', 'est_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_livraisons');
    }
};
