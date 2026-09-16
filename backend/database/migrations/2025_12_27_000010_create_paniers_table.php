<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paniers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('produit_id')->constrained('produits')->onDelete('cascade');
            $table->integer('quantite')->default(1);
            $table->decimal('prix_unitaire_actuel', 10, 2);
            $table->decimal('sous_total', 10, 2);
            $table->timestamp('date_expiration'); // 24h après création
            $table->timestamps();

            $table->index(['user_id', 'date_expiration']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paniers');
    }
};
