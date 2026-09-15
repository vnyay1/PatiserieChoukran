<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Suppression de ce que l'application n'utilise plus :
 * - commandes.livreur_id (doublon de vendeur_id depuis le rôle vendeur), devise (toujours XAF),
 *   operateur_mobile (écrit, jamais lu : moyen_paiement suffit) ;
 * - notifications.canal (toujours « app ») ;
 * - produits.nombre_vues (compteur jamais affiché) ;
 * - users.photo_profil, adresse_principale (jamais envoyés par le SPA), email_verified_at,
 *   remember_token (pas de vérification d'e-mail ni de session « se souvenir de moi ») ;
 * - tables sessions, cache, cache_locks (API sans état, cache en fichiers) et job_batches.
 */
return new class extends Migration
{
    private const TABLES = ['job_batches', 'cache_locks', 'cache', 'sessions'];

    public function up(): void
    {
        // Filet de sécurité : aucune commande ne perd son vendeur
        DB::table('commandes')
            ->whereNull('vendeur_id')
            ->whereNotNull('livreur_id')
            ->update(['vendeur_id' => DB::raw('livreur_id')]);

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropForeign(['livreur_id']);
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['livreur_id', 'devise', 'operateur_mobile']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('canal');
        });

        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn('nombre_vues');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['photo_profil', 'adresse_principale', 'email_verified_at', 'remember_token']);
        });

        foreach (self::TABLES as $nom) {
            Schema::dropIfExists($nom);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo_profil')->nullable();
            $table->text('adresse_principale')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
        });

        Schema::table('produits', function (Blueprint $table) {
            $table->integer('nombre_vues')->default(0);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('canal', ['app', 'email', 'sms', 'push'])->default('app');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->string('devise', 10)->default('XAF');
            $table->enum('operateur_mobile', ['orange', 'mtn'])->nullable();
            $table->foreignId('livreur_id')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('commandes')->update(['livreur_id' => DB::raw('vendeur_id')]);

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
    }
};
