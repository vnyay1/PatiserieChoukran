<?php

namespace Tests\Feature;

use App\Models\Adresse;
use App\Models\Commande;
use App\Models\ParametreSite;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class LivraisonStandardTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    private User $client;

    private User $vendeur;

    private Adresse $adresse;

    private Produit $produit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->creerUtilisateur('client');
        $this->vendeur = $this->creerUtilisateur('vendeur', ['nom_complet' => 'Chez Jean', 'montant_minimum_livraison' => 5000]);
        $quartier = $this->creerQuartier();
        $this->adresse = $this->creerAdresse($this->client, $quartier);
        $this->creerTarif($this->vendeur, $quartier);
        $this->produit = $this->creerProduit($this->vendeur, ['prix_unitaire' => 2000]);
    }

    public function test_les_frais_de_livraison_sont_le_parametre_de_la_plateforme(): void
    {
        $this->ajouterAuPanier(3);
        $this->postJson('/api/v1/commandes', $this->payloadLivraison())->assertCreated();

        $commande = Commande::firstOrFail();
        $this->assertEquals(1500, (float) $commande->montant_livraison);
        $this->assertEquals(7500, (float) $commande->montant_total);

        // L'admin change le tarif : les commandes suivantes l'appliquent
        ParametreSite::set('frais_livraison_standard', 2000, 'integer');
        $this->ajouterAuPanier(3);
        $this->postJson('/api/v1/commandes', $this->payloadLivraison())->assertCreated();

        $this->assertEquals(2000, (float) Commande::latest('id')->firstOrFail()->montant_livraison);
    }

    public function test_la_livraison_est_refusee_sous_le_minimum_du_vendeur(): void
    {
        $this->ajouterAuPanier(2);

        $response = $this->postJson('/api/v1/commandes', $this->payloadLivraison());

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame(
            'Livraison possible chez « Chez Jean » à partir de 5 000 FCFA d\'achat (il manque 1 000 FCFA) : ajoutez des produits ou choisissez le retrait en boutique.',
            $response->json('message')
        );
        $this->assertDatabaseCount('commandes', 0);
        $this->assertDatabaseCount('paniers', 1);
    }

    public function test_le_retrait_en_boutique_ignore_le_minimum_et_les_frais(): void
    {
        $this->ajouterAuPanier(1);

        $this->postJson('/api/v1/commandes', [
            'type_livraison' => 'retrait_boutique',
            'moyen_paiement' => 'especes',
        ])->assertCreated();

        $this->assertEquals(0, (float) Commande::firstOrFail()->montant_livraison);
    }

    public function test_passer_une_commande_en_livraison_reverifie_le_minimum(): void
    {
        $this->ajouterAuPanier(1);
        $this->postJson('/api/v1/commandes', [
            'type_livraison' => 'retrait_boutique',
            'moyen_paiement' => 'especes',
        ])->assertCreated();

        $commande = Commande::firstOrFail();

        $this->putJson("/api/v1/commandes/{$commande->id}", $this->payloadLivraison())
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame('retrait_boutique', $commande->fresh()->type_livraison);
    }

    public function test_le_vendeur_fixe_son_montant_minimum(): void
    {
        Sanctum::actingAs($this->vendeur);

        $this->getJson('/api/v1/vendeur/tarifs-livraison')
            ->assertOk()
            ->assertJsonPath('meta.montant_minimum_livraison', 5000)
            ->assertJsonPath('meta.frais_livraison_standard', 1500);

        $this->putJson('/api/v1/vendeur/livraison/minimum', ['montant_minimum_livraison' => -10])
            ->assertStatus(422)
            ->assertJsonValidationErrors('montant_minimum_livraison');

        $this->putJson('/api/v1/vendeur/livraison/minimum', ['montant_minimum_livraison' => 0])
            ->assertOk()
            ->assertJsonPath('data.montant_minimum_livraison', 0);

        // Plus de minimum : une petite commande peut être livrée
        $this->ajouterAuPanier(1);
        $this->postJson('/api/v1/commandes', $this->payloadLivraison())->assertCreated();
    }

    public function test_un_client_ne_peut_pas_modifier_le_minimum_d_un_vendeur(): void
    {
        Sanctum::actingAs($this->client);

        $this->putJson('/api/v1/vendeur/livraison/minimum', ['montant_minimum_livraison' => 0])->assertForbidden();
        $this->assertEquals(5000, (float) $this->vendeur->fresh()->montant_minimum_livraison);
    }

    private function ajouterAuPanier(int $quantite): void
    {
        Sanctum::actingAs($this->client);
        $this->postJson('/api/v1/panier', ['produit_id' => $this->produit->id, 'quantite' => $quantite])->assertCreated();
    }

    private function payloadLivraison(): array
    {
        return [
            'type_livraison' => 'livraison',
            'adresse_livraison_id' => $this->adresse->id,
            'telephone_livraison' => $this->client->telephone,
            'date_livraison_souhaitee' => now()->addDay()->toDateString(),
            'heure_livraison_souhaitee' => '10:00',
            'moyen_paiement' => 'especes',
        ];
    }
}
