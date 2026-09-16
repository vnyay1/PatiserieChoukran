<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le panier se vide après une durée d'inactivité réglée par l'admin
 * (paramètre panier_duree_minutes), calculée depuis sa dernière modification :
 * changer le réglage s'applique aussitôt à tous les paniers. La date d'expiration
 * figée à l'ajout (paniers.date_expiration) disparaît.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ancien = DB::table('parametre_sites')->where('cle', 'panier_expiration_heures')->value('valeur');

        DB::table('parametre_sites')->insertOrIgnore([
            'cle' => 'panier_duree_minutes',
            'valeur' => (string) ((int) $ancien > 0 ? (int) $ancien * 60 : 1440),
            'type' => 'integer',
            'description' => 'Durée (en minutes) après laquelle un panier sans modification est vidé automatiquement.',
            'groupe' => 'panier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('parametre_sites')->where('cle', 'panier_expiration_heures')->delete();

        // MySQL : l'ancien index sert aussi la clé étrangère user_id, le remplaçant
        // doit exister avant sa suppression ; SQLite : index supprimé avant la colonne.
        Schema::table('paniers', function (Blueprint $table) {
            $table->index(['user_id', 'updated_at']);
        });

        Schema::table('paniers', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'date_expiration']);
        });

        Schema::table('paniers', function (Blueprint $table) {
            $table->dropColumn('date_expiration');
        });
    }

    public function down(): void
    {
        $minutes = (int) (DB::table('parametre_sites')->where('cle', 'panier_duree_minutes')->value('valeur') ?: 1440);

        Schema::table('paniers', function (Blueprint $table) {
            $table->timestamp('date_expiration')->nullable()->after('sous_total');
        });

        DB::table('paniers')->orderBy('id')->each(function ($panier) use ($minutes) {
            DB::table('paniers')->where('id', $panier->id)->update([
                'date_expiration' => \Illuminate\Support\Carbon::parse($panier->updated_at ?? now())->addMinutes($minutes),
            ]);
        });

        Schema::table('paniers', function (Blueprint $table) {
            $table->index(['user_id', 'date_expiration']);
        });

        Schema::table('paniers', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'updated_at']);
        });

        DB::table('parametre_sites')->insertOrIgnore([
            'cle' => 'panier_expiration_heures',
            'valeur' => (string) max(1, intdiv($minutes, 60)),
            'type' => 'integer',
            'description' => 'Durée de vie du panier en heures.',
            'groupe' => 'panier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('parametre_sites')->where('cle', 'panier_duree_minutes')->delete();
    }
};
