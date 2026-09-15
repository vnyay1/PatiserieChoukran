<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Profil boutique obligatoire des vendeurs (e-mail, logo, description, acceptation
 * des conditions), affiché sur leur page publique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('logo_boutique')->nullable()->after('photo_profil');
            $table->text('description_boutique')->nullable()->after('logo_boutique');
            $table->timestamp('conditions_acceptees_le')->nullable()->after('description_boutique');
        });

        DB::table('parametre_sites')->insertOrIgnore([
            'cle' => 'conditions_vendeur',
            'valeur' => implode("\n\n", [
                'En vendant sur Choukrane Pâtisserie, je m\'engage à :',
                '1. Proposer des produits frais, préparés dans de bonnes conditions d\'hygiène, et décrits fidèlement (composition, allergènes, prix).',
                '2. Tenir mon stock à jour et confirmer ou refuser chaque commande dans les meilleurs délais.',
                '3. Livrer moi-même mes commandes dans les quartiers que j\'ai choisis, aux créneaux convenus, au tarif de livraison fixé par la plateforme.',
                '4. Encaisser les paiements (Orange Money, MTN MoMo ou espèces) de manière transparente ; la facture de chaque commande confirmée est remise au client.',
                '5. Garder confidentielles les données des clients et ne les utiliser que pour traiter leurs commandes.',
                '6. Accepter qu\'un manquement répété à ces règles entraîne la suspension de mon compte vendeur.',
            ]),
            'type' => 'string',
            'description' => 'Conditions que chaque vendeur doit accepter pour compléter son profil boutique.',
            'groupe' => 'vendeurs',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('parametre_sites')->where('cle', 'conditions_vendeur')->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['logo_boutique', 'description_boutique', 'conditions_acceptees_le']);
        });
    }
};
