<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La mise en avant passe du produit au vendeur : l'admin choisit des vendeurs
 * vedettes, dont tous les produits remontent en tête du catalogue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('est_vendeur_vedette')->default(false)->after('statut');
            $table->index('est_vendeur_vedette');
        });

        // Reprise : un vendeur qui avait un produit vedette devient vendeur vedette
        $vendeurIds = DB::table('produits')
            ->where('est_vedette', true)
            ->whereNotNull('created_by_user_id')
            ->distinct()
            ->pluck('created_by_user_id');

        DB::table('users')
            ->whereIn('id', $vendeurIds)
            ->where('role', 'vendeur')
            ->update(['est_vendeur_vedette' => true]);

        // Ordre imposé :
        // - MySQL : l'ancien index sert aussi la clé étrangère categorie_id ; le remplaçant
        //   doit exister avant de le supprimer (sinon erreur 1553) ;
        // - SQLite : l'index doit disparaître avant la colonne qu'il contient.
        Schema::table('produits', function (Blueprint $table) {
            $table->index(['categorie_id', 'est_disponible']);
        });

        Schema::table('produits', function (Blueprint $table) {
            $table->dropIndex(['categorie_id', 'est_disponible', 'est_vedette']);
        });

        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn('est_vedette');
        });
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->boolean('est_vedette')->default(false)->after('est_disponible');
        });

        Schema::table('produits', function (Blueprint $table) {
            $table->index(['categorie_id', 'est_disponible', 'est_vedette']);
        });

        Schema::table('produits', function (Blueprint $table) {
            $table->dropIndex(['categorie_id', 'est_disponible']);
        });

        $vendeurIds = DB::table('users')->where('est_vendeur_vedette', true)->pluck('id');
        DB::table('produits')->whereIn('created_by_user_id', $vendeurIds)->update(['est_vedette' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['est_vendeur_vedette']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('est_vendeur_vedette');
        });
    }
};
