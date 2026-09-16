<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La livraison se choisit par ville et non plus par quartier, sans délai :
 * - vendeur_villes remplace vendeur_tarifs_livraison (quartier + délais) ;
 * - l'ancien système de zones (zone_livraisons, livreur_zone_livraisons,
 *   adresses.zone_livraison_id), qui recopiait la liste des quartiers, disparaît ;
 * - l'adresse gagne une « zone » libre et facultative (secteur, carrefour…).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendeur_villes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendeur_id')->constrained('users')->cascadeOnDelete();
            $table->enum('ville', ['yaoundé', 'douala']);
            $table->timestamps();

            $table->unique(['vendeur_id', 'ville']);
            $table->index('ville');
        });

        // Reprise : un vendeur livre les villes dont il desservait au moins un quartier actif
        $villes = DB::table('vendeur_tarifs_livraison')
            ->join('quartiers', 'quartiers.id', '=', 'vendeur_tarifs_livraison.quartier_id')
            ->where('vendeur_tarifs_livraison.actif', true)
            ->select('vendeur_tarifs_livraison.vendeur_id', 'quartiers.ville')
            ->distinct()
            ->get();

        DB::table('vendeur_villes')->insertOrIgnore($villes->map(fn ($ligne) => [
            'vendeur_id' => $ligne->vendeur_id,
            'ville' => $ligne->ville,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        Schema::dropIfExists('vendeur_tarifs_livraison');

        Schema::table('adresses', function (Blueprint $table) {
            $table->string('zone', 150)->nullable()->after('quartier_id');
        });

        Schema::table('adresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zone_livraison_id');
        });

        // Les tables qui référencent zone_livraisons disparaissent avant elle
        Schema::dropIfExists('livreur_zone_livraisons');
        Schema::dropIfExists('zone_livraisons');
    }

    public function down(): void
    {
        Schema::create('zone_livraisons', function (Blueprint $table) {
            $table->id();
            $table->string('nom_zone');
            $table->string('ville');
            $table->decimal('tarif_livraison', 10, 2);
            $table->integer('delai_livraison_min');
            $table->integer('delai_livraison_max');
            $table->boolean('est_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ville', 'est_active']);
            $table->index('created_by_user_id');
        });

        Schema::create('livreur_zone_livraisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livreur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('zone_livraison_id')->constrained('zone_livraisons')->cascadeOnDelete();
            $table->decimal('tarif_livraison', 10, 2);
            $table->integer('delai_livraison_min');
            $table->integer('delai_livraison_max');
            $table->timestamps();

            $table->unique(['livreur_id', 'zone_livraison_id'], 'livreur_zone_unique');
        });

        Schema::table('adresses', function (Blueprint $table) {
            $table->foreignId('zone_livraison_id')->nullable()->after('ville')->constrained('zone_livraisons')->nullOnDelete();
        });

        Schema::table('adresses', function (Blueprint $table) {
            $table->dropColumn('zone');
        });

        Schema::create('vendeur_tarifs_livraison', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendeur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('quartier_id')->constrained('quartiers')->cascadeOnDelete();
            $table->unsignedSmallInteger('delai_min')->default(30);
            $table->unsignedSmallInteger('delai_max')->default(60);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['vendeur_id', 'quartier_id']);
        });

        // Chaque ville livrée redevient l'ensemble de ses quartiers actifs
        $dessertes = DB::table('vendeur_villes')
            ->join('quartiers', 'quartiers.ville', '=', 'vendeur_villes.ville')
            ->where('quartiers.actif', true)
            ->select('vendeur_villes.vendeur_id', 'quartiers.id as quartier_id')
            ->get();

        foreach ($dessertes->chunk(500) as $lot) {
            DB::table('vendeur_tarifs_livraison')->insertOrIgnore($lot->map(fn ($ligne) => [
                'vendeur_id' => $ligne->vendeur_id,
                'quartier_id' => $ligne->quartier_id,
                'created_at' => now(),
                'updated_at' => now(),
            ])->values()->all());
        }

        Schema::dropIfExists('vendeur_villes');
    }
};
