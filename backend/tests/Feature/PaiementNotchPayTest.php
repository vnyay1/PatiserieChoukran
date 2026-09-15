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
            'api.notchpay.co/payments' => Http::response([
                'status' => 'Accepted',
                'transaction' => ['id' => 'trx.abc', 'status' => 'pending'],
                'authorization_url' => 'https://pay.notchpay.co/trx.abc',
            ], 201),
        ]);

        $this->remplirPanier();
        $reponse = $this->postJson('/api/v1/commandes', [
            'type_livraison' => 'retrait_boutique',
            'moyen_paiement' => 'orange_money',
            'telephone_paiement' => '+237690000999',
        ])->assertCreated();

        $reponse->assertJsonPath('paiement.url_paiement', 'https://pay.notchpay.co/trx.abc')
            ->assertJsonCount(2, 'data');

        $paiement = Paiement::firstOrFail();
        $this->assertSame($reponse->json('paiement.reference'), $paiement->reference);
        $this->assertEquals(5000, (float) $paiement->montant);
        $this->assertSame('trx.abc', $paiement->notchpay_id);
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

        Http::fake(["api.notchpay.co/payments/{$paiement->reference}" => Http::sequence()
            ->push(['transaction' => ['status' => 'pending']])
            ->push(['transaction' => ['status' => 'complete']])]);

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

        // Un autre client ne voit pas ce paiement
        Sanctum::actingAs($this->creerUtilisateur('client'));
        $this->getJson("/api/v1/paiements/{$paiement->reference}")->assertNotFound();
    }

    public function test_le_webhook_signe_confirme_le_paiement_une_seule_fois(): void
    {
        [$paiement, $commandes] = $this->paiementEnAttente();
        Http::fake(["api.notchpay.co/payments/{$paiement->reference}" => Http::response(['transaction' => ['status' => 'complete']])]);

        $corps = json_encode(['type' => 'payment.complete', 'data' => ['reference' => $paiement->reference, 'status' => 'complete']]);

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
            "api.notchpay.co/payments/{$paiement->reference}" => Http::response(['transaction' => ['status' => 'canceled']]),
            'api.notchpay.co/payments' => Http::response(['authorization_url' => 'https://pay.notchpay.co/nouveau', 'transaction' => ['id' => 'trx.2']], 201),
        ]);

        Sanctum::actingAs($this->client);
        $this->getJson("/api/v1/paiements/{$paiement->reference}")->assertJsonPath('data.statut', 'annule');
        $this->assertSame('echec', $commandes->first()->fresh()->statut_paiement);

        $this->postJson("/api/v1/commandes/{$commandes->first()->id}/payer")
            ->assertOk()
            ->assertJsonPath('data.url_paiement', 'https://pay.notchpay.co/nouveau');

        $this->assertNotSame($paiement->id, $commandes->first()->fresh()->paiement_id);

        // Commande en espèces : pas de paiement en ligne
        $especes = $this->creerCommande($this->client, $this->vendeurA);
        $this->postJson("/api/v1/commandes/{$especes->id}/payer")->assertStatus(422);
    }

    public function test_le_callback_sur_l_api_renvoie_vers_la_page_de_retour_du_spa(): void
    {
        config(['app.frontend_url' => 'http://localhost:5173']);

        $this->get('/payments/callback?reference=trx.abc&status=complete')
            ->assertRedirect('http://localhost:5173/paiement/retour?reference=trx.abc&status=complete');
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
        ]);
        Commande::whereIn('id', $commandes->pluck('id'))->update(['paiement_id' => $paiement->id]);

        return [$paiement, $commandes];
    }

    private function entetes(string $signature): array
    {
        return ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_NOTCH_SIGNATURE' => $signature];
    }
}
