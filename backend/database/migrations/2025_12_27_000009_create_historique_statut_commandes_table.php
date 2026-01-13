<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_statut_commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->string('ancien_statut', 50)->nullable();
            $table->string('nouveau_statut', 50);
            $table->text('commentaire')->nullable();
            $table->foreignId('modifie_par_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('created_at');
            
            $table->index(['commande_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_statut_commandes');
    }
};
