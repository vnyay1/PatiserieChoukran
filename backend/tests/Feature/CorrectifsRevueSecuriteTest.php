<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\Paiement;
use App\Models\ParametreSite;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

/**
 * Points relevés par la relecture sécurité des correctifs (agent revue-securite-marketplace).
 */
class CorrectifsRevueSecuriteTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    private User $client;

    private User $vendeur;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.notchpay.public_key' => 'pk_test_choukrane']);
        $this->client = $this->creerUtilisateur('client');
        $this->vendeur = $this->creerUtilisateur('vendeur');
    }

    public function test_l_adresse_d_une_commande_en_cours_garde_son_quartier_et_ne_disparait_pas(): void
    {
        $yaounde = $this->creerQuartier('Bastos', 'yaoundé');
        $douala = $this->creerQuartier('Akwa', 'douala');
        $adresse = $this->creerAdresse($this->client, $yaounde);
        $this->creerCommande($this->client, $this->vendeur, ['type_livraison' => 'livraison', 'adresse_livraison_id' => $adresse->id]);

        Sanctum::actingAs($this->client);
        $this->putJson("/api/v1/adresses/{$adresse->id}", ['ville' => 'douala', 'quartier_id' => $douala->id])->assertStatus(422);
        $this->assertSame($yaounde->id, $adresse->fresh()->quartier_id);

        // Un repère ou un numéro de contact peut encore être précisé
        $this->putJson("/api/v1/adresses/{$adresse->id}", ['point_repere' => 'Face à la pharmacie'])->assertOk();

        $this->deleteJson("/api/v1/adresses/{$adresse->id}")->assertStatus(422);
        $this->assertModelExists($adresse);
    }

    public function test_payer_reprend_le_paiement_deja_ouvert_au_lieu_d_en_creer_un_second(): void
    {
        [$commande] = $this->commandeAvecPaiement('en_attente', ['url_paiement' => 'https://pay.notchpay.co/p1']);
        Http::fake(['api.notchpay.co/payments/trx.p1' => Http::response(['transaction' => ['reference' => 'trx.p1', 'status' => 'pending']])]);

        Sanctum::actingAs($this->client);
        $this->postJson("/api/v1/commandes/{$commande->id}/payer")
            ->assertOk()
            ->assertJsonPath('data.url_paiement', 'https://pay.notchpay.co/p1');

        $this->assertDatabaseCount('paiements', 1);
    }

    public function test_un_paiement_recu_en_attente_de_verification_bloque_la_commande(): void
    {
        [$commande] = $this->commandeAvecPaiement('complete');

        Sanctum::actingAs($this->client);
        $this->postJson("/api/v1/commandes/{$commande->id}/cancel")->assertStatus(422);
        $this->putJson("/api/v1/commandes/{$commande->id}", ['moyen_paiement' => 'especes'])->assertStatus(422);
        $this->postJson("/api/v1/commandes/{$commande->id}/payer")->assertStatus(422);

        $this->assertSame('en_attente', $commande->fresh()->statut);
        $this->assertDatabaseCount('paiements', 1);
    }

    public function test_un_paiement_ouvert_depuis_plus_d_un_jour_ne_fige_plus_la_commande(): void
    {
        [$commande, $paiement] = $this->commandeAvecPaiement('en_attente');
        Paiement::whereKey($paiement->id)->update(['created_at' => now()->subDays(2)]);
        Http::fake(['api.notchpay.co/payments/trx.p1' => Http::response(['transaction' => ['reference' => 'trx.p1', 'status' => 'pending']])]);

        Sanctum::actingAs($this->client);
        $this->putJson("/api/v1/commandes/{$commande->id}", ['moyen_paiement' => 'especes'])->assertOk();
    }

    public function test_une_commande_payee_garde_son_montant_quand_les_frais_changent(): void
    {
        $adresse = $this->creerAdresse($this->client, $this->creerQuartier());
        $this->livrerVille($this->vendeur);
        $commande = $this->creerCommande($this->client, $this->vendeur, [
            'type_livraison' => 'livraison',
            'adresse_livraison_id' => $adresse->id,
            'telephone_livraison' => '+237690000999',
            'montant_produits' => 5000,
            'montant_livraison' => 1500,
            'montant_total' => 6500,
            'statut_paiement' => 'paye',
        ]);
        ParametreSite::set('frais_livraison_standard', '2500', 'integer');

        Sanctum::actingAs($this->client);
        // Le SPA renvoie tous les champs du formulaire, inchangés
        $this->putJson("/api/v1/commandes/{$commande->id}", [
            'type_livraison' => 'livraison',
            'adresse_livraison_id' => $adresse->id,
            'telephone_livraison' => '+237690000999',
            'moyen_paiement' => 'especes',
            'instructions_speciales' => 'Sonner deux fois',
        ])->assertOk();

        $this->assertEquals(6500, (float) $commande->fresh()->montant_total);
        $this->assertSame('Sonner deux fois', $commande->fresh()->instructions_speciales);
    }

    public function test_un_numero_enregistre_avant_la_normalisation_ne_bloque_pas_une_commande_payee(): void
    {
        $commande = $this->creerCommande($this->client, $this->vendeur, [
            'moyen_paiement' => 'mtn_momo',
            'telephone_paiement' => '690 00 09 99',
            'statut_paiement' => 'paye',
        ]);

        Sanctum::actingAs($this->client);
        $this->putJson("/api/v1/commandes/{$commande->id}", [
            'moyen_paiement' => 'mtn_momo',
            'telephone_paiement' => '690 00 09 99',
            'instructions_speciales' => 'Merci',
        ])->assertOk();
    }

    public function test_un_mot_de_passe_qui_a_la_forme_d_un_hash_est_refuse(): void
    {
        $hash = password_hash('a', PASSWORD_BCRYPT);

        $this->postJson('/api/v1/auth/register', [
            'nom_complet' => 'Cliente',
            'telephone' => '690765432',
            'mot_de_passe' => $hash,
            'mot_de_passe_confirmation' => $hash,
        ])->assertStatus(422)->assertJsonValidationErrors('mot_de_passe');
    }

    public function test_les_tableaux_inattendus_donnent_une_erreur_de_validation(): void
    {
        Sanctum::actingAs($this->client);
        $this->postJson('/api/v1/panier', ['produit_id' => [1], 'quantite' => 1])->assertStatus(422);
        $this->postJson('/api/v1/commandes', [
            'type_livraison' => 'livraison',
            'adresse_livraison_id' => [1],
            'telephone_livraison' => '690000999',
            'moyen_paiement' => 'especes',
        ])->assertStatus(422);
        $this->putJson('/api/v1/auth/profile', ['email' => ['a@b.cm']])->assertStatus(422);

        Sanctum::actingAs($this->vendeur);
        $this->putJson('/api/v1/auth/profile', ['telephone' => '+237699999998', 'mot_de_passe_actuel' => ['x']])->assertStatus(422);
        $this->postJson('/api/v1/vendeur/catalogue/produits', [
            'categorie_id' => [1], 'nom' => 'Tarte', 'prix_unitaire' => 1000, 'stock_disponible' => 3,
        ])->assertStatus(422);

        Sanctum::actingAs($this->creerUtilisateur('admin'));
        $this->postJson('/api/v1/admin/parametres', ['cle' => 'essai', 'type' => ['json'], 'valeur' => 'x'])->assertStatus(422);
    }

    public function test_une_taille_de_page_vide_donne_la_taille_par_defaut(): void
    {
        Sanctum::actingAs($this->creerUtilisateur('admin'));
        $this->getJson('/api/v1/admin/users?per_page=')->assertOk()->assertJsonPath('data.per_page', 15);
        $this->getJson('/api/v1/admin/users?per_page=abc')->assertOk()->assertJsonPath('data.per_page', 15);
    }

    public function test_la_limite_de_connexion_d_un_compte_vaut_pour_toutes_les_adresses_ip(): void
    {
        config(['app.proxies_de_confiance' => '*']);
        $this->app->getProvider(AppServiceProvider::class)->boot();

        try {
            // Force brute répartie : une seule tentative par adresse
            for ($i = 1; $i <= 50; $i++) {
                $this->postJson('/api/v1/auth/login', ['telephone' => $this->client->telephone, 'mot_de_passe' => 'mauvais'], ['X-Forwarded-For' => "198.51.100.{$i}"])
                    ->assertStatus(422);
            }

            $this->postJson('/api/v1/auth/login', ['telephone' => $this->client->telephone, 'mot_de_passe' => 'mauvais'], ['X-Forwarded-For' => '203.0.113.7'])
                ->assertStatus(429);
        } finally {
            TrustProxies::flushState();
        }
    }

    public function test_le_paiement_et_le_mot_de_passe_ont_des_compteurs_distincts(): void
    {
        Sanctum::actingAs($this->client);
        $mauvais = ['ancien_mot_de_passe' => 'faux-mot-de-passe', 'nouveau_mot_de_passe' => 'nouveau-1234', 'nouveau_mot_de_passe_confirmation' => 'nouveau-1234'];
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/change-password', $mauvais)->assertStatus(400);
        }

        // Le compteur du mot de passe est épuisé, pas celui des paiements
        $especes = $this->creerCommande($this->client, $this->vendeur);
        $this->postJson("/api/v1/commandes/{$especes->id}/payer")->assertStatus(422);
    }

    /**
     * Commande mobile money rattachée à un paiement NotchPay dans l'état donné.
     *
     * @return array{0: Commande, 1: Paiement}
     */
    private function commandeAvecPaiement(string $statut, array $attributs = []): array
    {
        $commande = $this->creerCommande($this->client, $this->vendeur, ['moyen_paiement' => 'mtn_momo', 'telephone_paiement' => '+237690000999']);
        $paiement = Paiement::create([
            'reference' => 'CHK-REVUE-0001',
            'user_id' => $this->client->id,
            'montant' => 2000,
            'moyen_paiement' => 'mtn_momo',
            'notchpay_id' => 'trx.p1',
            'statut' => $statut,
            ...$attributs,
        ]);
        $commande->update(['paiement_id' => $paiement->id]);

        return [$commande->fresh(), $paiement];
    }
}
