<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index des listes les plus consultées :
 * - commandes d'un vendeur filtrées par statut et triées par date (liste et badge vendeur) ;
 * - nouveautés de la page d'accueil (produits disponibles triés par date).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->index(['vendeur_id', 'statut', 'created_at']);
        });

        Schema::table('produits', function (Blueprint $table) {
            $table->index(['est_disponible', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropIndex(['est_disponible', 'created_at']);
        });

        // MySQL a remplacé l'index de la clé étrangère vendeur_id par l'index composé :
        // on le recrée avant de supprimer ce dernier (sinon erreur 1553)
        if (! Schema::hasIndex('commandes', 'commandes_vendeur_id_foreign')
            && in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('commandes', function (Blueprint $table) {
                $table->index('vendeur_id', 'commandes_vendeur_id_foreign');
            });
        }

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropIndex(['vendeur_id', 'statut', 'created_at']);
        });
    }
};
