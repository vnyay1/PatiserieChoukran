<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->after('est_actif')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('created_by_user_id');
        });

        Schema::table('produits', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->after('categorie_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('created_by_user_id');
        });

        Schema::table('zone_livraisons', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->after('est_active')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('created_by_user_id');
        });
    }

    public function down(): void
    {
        // L'index doit disparaître avant la colonne : SQLite refuse de supprimer
        // une colonne encore référencée par un index (MySQL le tolère).
        foreach (['categories', 'produits', 'zone_livraisons'] as $nomTable) {
            Schema::table($nomTable, function (Blueprint $table) {
                $table->dropForeign(['created_by_user_id']);
                $table->dropIndex(['created_by_user_id']);
                $table->dropColumn('created_by_user_id');
            });
        }
    }
};
