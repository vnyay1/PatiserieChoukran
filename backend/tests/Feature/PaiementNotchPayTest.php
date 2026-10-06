<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as RequeteHttp;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class PaiementNotchPayTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    private const SECRET_WEBHOOK = 'secret-de-test';

    private User $client;

    private User $vendeurA;

    private User $vendeurB;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.notchpay.public_key' => 'pk_test_choukrane',
            'services.notchpay.webhook_hash' => self::SECRET_WEBHOOK,
            'services.notchpay.callback_url' => 'http://localhost:5173/paiement/retour',
        ]);

        $this->client = $this->creerUtilisateur('client', ['email' => 'client@exemple.cm']);
        $this->vendeurA = $this->creerUtilisateur('vendeur');
        $this->vendeurB = $this->creerUtilisateur('vendeur');
    }

    public function test_le_checkout_mobile_money_cree_un_seul_paiement_notchpay_pour_toutes_les_commandes(): void
    {
        Http::fake([
            'api.notchpay.co/payments' => fn (RequeteHttp $requete) => Http::response([
                'status' => 'Accepted',
                'code' => 201,
                'transaction' => [
                    'reference' => 'trx.test_abc',
                    'merchant_reference' => $requete['reference'],
                    'trxref' => $requete['reference'],
                    'status' => 'pending',
                ],
                'authorization_url' => 'https://pay.notchpay.co/test.abc',
            ], 201),
        ]);

        $this->remplirPanier();
        $reponse = $this->postJson('/api/v1/commandes', [
            'type_livraison' => 'retrait_boutique',
            'moyen_paiement' => 'orange_money',
            'telephone_paiement' => '+237690000999',
        ])->assertCreated();

        $reponse->assertJsonPath('paiement.url_paiement', 'https://pay.notchpay.co/test.abc')
            ->assertJsonCount(2, 'data');

        $paiement = Paiement::firstOrFail();
        $this->assertSame($reponse->json('paiement.reference'), $paiement->reference);
        $this->assertEquals(5000, (float) $paiement->montant);
        $this->assertSame('trx.test_abc', $paiement->notchpay_id);
        $this->assertSame(2, Commande::where('paiement_id', $paiement->id)->count());

        Http::assertSent(fn (RequeteHttp $requete) => $requete->url() === 'https://api.notchpay.co/payments'
            && $requete->hasHeader('Authorization', 'pk_test_choukrane')
            && $requete['amount'] === 5000
            && $requete['currency'] === 'XAF'
            && $requete['reference'] === $paiement->reference
            && $requete['callback'] === 'http://localhost:5173/paiement/retour');
    }

    public function test_sans_cle_notchpay_ou_en_especes_aucun_paiement_en_ligne(): void
    {
        config(['services.notchpay.public_key' => null]);
        $this->remplirPanier();

        $this->postJson('/api/v1/commandes', [
            'type_livraison' => 'retrait_boutique',
            'moyen_paiement' => 'mtn_momo',
            'telephone_paiement' => '+237690000999',
        ])->assertCreated()->assertJsonPath('paiement', null);

        $this->assertDatabaseCount('paiements', 0);
        Http::assertNothingSent();
    }

    public function test_notchpay_injoignable_les_commandes_restent_creees(): void
    {
        Http::fake(['api.notchpay.co/payments' => Http::response(['message' => 'Service unavailable'], 503)]);
        $this->remplirPanier();

        $this->postJson('/api/v1/commandes', [
            'type_livraison' => 'retrait_boutique',
            'moyen_paiement' => 'orange_money',
            'telephone_paiement' => '+237690000999',
        ])
            ->assertCreated()
            ->assertJsonPath('paiement', null)
            ->assertJsonPath('erreur_paiement', 'Le paiement en ligne est momentanément indisponible. Réessayez depuis le détail de la commande.');

        $this->assertDatabaseCount('commandes', 2);
        $this->assertSame('echec', Paiement::firstOrFail()->statut);
    }

    public function test_le_retour_du_client_verifie_le_statut_aupres_de_notchpay(): void
    {
        [$paiement, $commandes] = $this->paiementEnAttente();

        Http::fake([
            'api.notchpay.co/payments/trx.test_1' => Http::sequence()
                ->push(['transaction' => ['reference' => 'trx.test_1', 'status' => 'pending']])
                ->push(['transaction' => ['reference' => 'trx.test_1', 'status' => 'complete']]),
            'api.notchpay.co/payments/*' => Http::response(['message' => 'Payment Not Found'], 404),
        ]);

        Sanctum::actingAs($this->client);
        $this->getJson("/api/v1/paiements/{$paiement->reference}")->assertOk()->assertJsonPath('data.statut', 'en_attente');
        $this->getJson("/api/v1/paiements/{$paiement->reference}")
            ->assertOk()
            ->assertJsonPath('data.statut', 'complete')
            ->assertJsonPath('data.commandes.0.statut_paiement', 'paye');

        foreach ($commandes as $commande) {
            $commande->refresh();
            $this->assertSame('paye', $commande->statut_paiement);
            $this->assertSame("NotchPay {$paiement->reference}", $commande->reference_paiement);
        }

        Http::assertNotSent(fn (RequeteHttp $requete) => str_contains($requete->url(), 'CHK-TEST-0001'));

        // Retour avec la référence NotchPay ajoutée par la page de paiement
        $this->getJson('/api/v1/paiements/trx.test_1')->assertOk()->assertJsonPath('data.reference', 'CHK-TEST-0001');

        // Un autre client ne voit pas ce paiement
        Sanctum::actingAs($this->creerUtilisateur('client'));
        $this->getJson("/api/v1/paiements/{$paiement->reference}")->assertNotFound();
    }

    public function test_le_webhook_signe_confirme_le_paiement_une_seule_fois(): void
    {
        [$paiement, $commandes] = $this->paiementEnAttente();
        Http::fake(['api.notchpay.co/payments/trx.test_1' => Http::response(['transaction' => ['reference' => 'trx.test_1', 'status' => 'complete']])]);

        // Format réel : data.reference est la référence NotchPay, la nôtre est dans merchant_reference
        $corps = json_encode(['type' => 'payment.complete', 'data' => ['reference' => 'trx.test_1', 'merchant_reference' => $paiement->reference, 'status' => 'complete']]);

        $this->call('POST', '/api/v1/webhooks/notchpay', [], [], [], $this->entetes('signature-fausse'), $corps)
            ->assertStatus(401);
        $this->assertSame('en_attente', $paiement->fresh()->statut);

        $signature = hash_hmac('sha256', $corps, self::SECRET_WEBHOOK);
        $this->call('POST', '/api/v1/webhooks/notchpay', [], [], [], $this->entetes($signature), $corps)->assertOk();
        $this->call('POST', '/api/v1/webhooks/notchpay', [], [], [], $this->entetes($signature), $corps)->assertOk();

        $this->assertSame('complete', $paiement->fresh()->statut);
        $this->assertSame(2, Commande::where('statut_paiement', 'paye')->count());
        // Une notification « paiement reçu » par commande, pas une par webhook
        $this->assertDatabaseCount('notifications', $commandes->count());
        // Le statut vient de l'API (vérification), pas seulement du corps du webhook
        Http::assertSentCount(1);
    }

    public function test_un_paiement_echoue_peut_etre_relance_depuis_la_commande(): void
    {
        [$paiement, $commandes] = $this->paiementEnAttente();
        Http::fake([
            'api.notchpay.co/payments/trx.test_1' => Http::response(['transaction' => ['reference' => 'trx.test_1', 'status' => 'canceled']]),
            'api.notchpay.co/payments' => Http::response(['authorization_url' => 'https://pay.notchpay.co/nouveau', 'transaction' => ['reference' => 'trx.test_2']], 201),
        ]);

        Sanctum::actingAs($this->client);
        $this->getJson("/api/v1/paiements/{$paiement->reference}")->assertJsonPath('data.statut', 'annule');
        $this->assertSame('echec', $commandes->first()->fresh()->statut_paiement);

        $this->postJson("/api/v1/commandes/{$commandes->first()->id}/payer")
            ->assertOk()
            ->assertJsonPath('data.url_paiement', 'https://pay.notchpay.co/nouveau');

        $this->assertNotSame($paiement->id, $commandes->first()->fresh()->paiement_id);
        $this->assertSame('trx.test_2', $commandes->first()->fresh()->paiement->notchpay_id);

        // Commande en espèces : pas de paiement en ligne
        $especes = $this->creerCommande($this->client, $this->vendeurA);
        $this->postJson("/api/v1/commandes/{$especes->id}/payer")->assertStatus(422);
    }

    public function test_payer_ne_rouvre_pas_un_paiement_dont_une_autre_commande_a_ete_annulee(): void
    {
        // Paiement de 4 000 pour deux commandes de 2 000 ; le vendeur B annule la sienne ensuite
        [$paiement, $commandes] = $this->paiementEnAttente();
        $paiement->update(['url_paiement' => 'https://pay.notchpay.co/ancien']);
        $commandes->last()->changerStatut('annulee', $this->vendeurB->id, 'Rupture de stock');

        Http::fake([
            'api.notchpay.co/payments/trx.test_1' => Http::response(['transaction' => ['reference' => 'trx.test_1', 'status' => 'pending']]),
            'api.notchpay.co/payments' => Http::response(['authorization_url' => 'https://pay.notchpay.co/nouveau', 'transaction' => ['reference' => 'trx.test_2']], 201),
        ]);

        // L'ancien lien ferait payer 4 000 pour une commande qui n'en doit plus que 2 000
        Sanctum::actingAs($this->client);
        $this->postJson("/api/v1/commandes/{$commandes->first()->id}/payer")
            ->assertOk()
            ->assertJsonPath('data.url_paiement', 'https://pay.notchpay.co/nouveau');

        $commande = $commandes->first()->fresh();
        $this->assertNotSame($paiement->id, $commande->paiement_id);
        $this->assertEquals(2000, (float) $commande->paiement->montant);
        Http::assertSent(fn (RequeteHttp $requete) => $requete->url() === 'https://api.notchpay.co/payments' && $requete['amount'] === 2000);
    }

    public function test_le_callback_sur_l_api_renvoie_vers_la_page_de_retour_du_spa(): void
    {
        config(['app.frontend_url' => 'http://localhost:5173']);

        $this->get('/payments/callback?reference=trx.abc&status=complete')
            ->assertRedirect('http://localhost:5173/paiement/retour?reference=trx.abc&status=complete');
    }

    public function test_le_montant_exact_en_xaf_confirme_les_commandes(): void
    {
        [$paiement] = $this->paiementEnAttente();
        $this->statutNotchPay(['status' => 'complete', 'amount' => 4000, 'currency' => 'XAF']);

        Sanctum::actingAs($this->client);
        $this->getJson("/api/v1/paiements/{$paiement->reference}")->assertOk()->assertJsonPath('data.statut', 'complete');

        $this->assertSame(2, Commande::where('statut_paiement', 'paye')->count());
    }

    public function test_un_montant_recu_different_ne_confirme_pas_les_commandes_et_previent_l_admin(): void
    {
        $admin = $this->creerUtilisateur('admin');
        [$paiement, $commandes] = $this->paiementEnAttente();
        $this->statutNotchPay(['status' => 'complete', 'amount' => 2500, 'currency' => 'XAF']);

        Sanctum::actingAs($this->client);
        // L'argent est bien arrivé chez NotchPay : le paiement est terminé…
        $this->getJson("/api/v1/paiements/{$paiement->reference}")->assertOk()->assertJsonPath('data.statut', 'complete');

        // … mais les commandes attendent une vérification humaine
        foreach ($commandes as $commande) {
            $this->assertSame('en_attente', $commande->fresh()->statut_paiement);
        }
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'type' => 'systeme']);
    }

    public function test_une_commande_dont_le_total_a_change_apres_le_debut_du_paiement_n_est_pas_confirmee(): void
    {
        $admin = $this->creerUtilisateur('admin');
        [$paiement, $commandes] = $this->paiementEnAttente();
        // Frais de livraison ajoutés après l'ouverture du paiement de 4000 FCFA
        $commandes->first()->update(['montant_livraison' => 1500, 'montant_total' => 3500]);
        $this->statutNotchPay(['status' => 'complete', 'amount' => 4000, 'currency' => 'XAF']);

        Sanctum::actingAs($this->client);
        $this->getJson("/api/v1/paiements/{$paiement->reference}")->assertOk();

        $this->assertSame(0, Commande::where('statut_paiement', 'paye')->count());
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'type' => 'systeme']);
    }

    public function test_la_livraison_ne_change_plus_pendant_un_paiement_en_cours(): void
    {
        [, $commandes] = $this->paiementEnAttente();
        $this->statutNotchPay(['status' => 'pending']);
        $commande = $commandes->first();
        $adresse = $this->creerAdresse($this->client, $this->creerQuartier());
        $this->livrerVille($this->vendeurA);

        Sanctum::actingAs($this->client);
        $this->putJson("/api/v1/commandes/{$commande->id}", [
            'type_livraison' => 'livraison',
            'adresse_livraison_id' => $adresse->id,
            'telephone_livraison' => '+237690000999',
        ])->assertStatus(422);
        $this->assertSame('retrait_boutique', $commande->fresh()->type_livraison);
        $this->assertEquals(2000, (float) $commande->fresh()->montant_total);

        // Les instructions pour le vendeur restent modifiables
        $this->putJson("/api/v1/commandes/{$commande->id}", ['instructions_speciales' => 'Sonner deux fois'])->assertOk();
    }

    public function test_un_paiement_expire_libere_la_commande(): void
    {
        [, $commandes] = $this->paiementEnAttente();
        $this->statutNotchPay(['status' => 'expired']);
        $commande = $commandes->first();
        $adresse = $this->creerAdresse($this->client, $this->creerQuartier());
        $this->livrerVille($this->vendeurA);

        Sanctum::actingAs($this->client);
        $this->putJson("/api/v1/commandes/{$commande->id}", [
            'type_livraison' => 'livraison',
            'adresse_livraison_id' => $adresse->id,
            'telephone_livraison' => '+237690000999',
        ])->assertOk();
        $this->assertSame('livraison', $commande->fresh()->type_livraison);
    }

    public function test_une_commande_payee_ne_change_plus_de_livraison(): void
    {
        $commande = $this->creerCommande($this->client, $this->vendeurA, ['statut_paiement' => 'paye']);
        $adresse = $this->creerAdresse($this->client, $this->creerQuartier());
        $this->livrerVille($this->vendeurA);

        Sanctum::actingAs($this->client);
        $this->putJson("/api/v1/commandes/{$commande->id}", [
            'type_livraison' => 'livraison',
            'adresse_livraison_id' => $adresse->id,
            'telephone_livraison' => '+237690000999',
        ])->assertStatus(422);
        $this->assertSame('retrait_boutique', $commande->fresh()->type_livraison);
    }

    // Réponse de GET /payments/trx.test_1 (paiement créé par paiementEnAttente())
    private function statutNotchPay(array $transaction): void
    {
        Http::fake([
            'api.notchpay.co/payments/trx.test_1' => Http::response(['transaction' => ['reference' => 'trx.test_1', ...$transaction]]),
        ]);
    }

    private function remplirPanier(): void
    {
        $produitA = $this->creerProduit($this->vendeurA, ['prix_unitaire' => 3000]);
        $produitB = $this->creerProduit($this->vendeurB, ['prix_unitaire' => 2000]);

        Sanctum::actingAs($this->client);
        $this->postJson('/api/v1/panier', ['produit_id' => $produitA->id, 'quantite' => 1])->assertCreated();
        $this->postJson('/api/v1/panier', ['produit_id' => $produitB->id, 'quantite' => 1])->assertCreated();
    }

    private function paiementEnAttente(): array
    {
        $commandes = collect([
            $this->creerCommande($this->client, $this->vendeurA, ['moyen_paiement' => 'orange_money', 'telephone_paiement' => '+237690000999']),
            $this->creerCommande($this->client, $this->vendeurB, ['moyen_paiement' => 'orange_money', 'telephone_paiement' => '+237690000999']),
        ]);

        $paiement = Paiement::create([
            'reference' => 'CHK-TEST-0001',
            'user_id' => $this->client->id,
            'montant' => 4000,
            'moyen_paiement' => 'orange_money',
            'notchpay_id' => 'trx.test_1',
        ]);
        Commande::whereIn('id', $commandes->pluck('id'))->update(['paiement_id' => $paiement->id]);

        return [$paiement, $commandes];
    }

    private function entetes(string $signature): array
    {
        return ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_NOTCH_SIGNATURE' => $signature];
    }
}
