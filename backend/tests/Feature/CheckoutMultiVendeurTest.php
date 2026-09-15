<?php

namespace Tests\Feature;

use App\Models\Adresse;
use App\Models\Commande;
use App\Models\Produit;
use App\Models\Quartier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class CheckoutMultiVendeurTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    private Quartier $bastos;

    private Quartier $akwa;

    private User $vendeurA;

    private User $vendeurB;

    private User $client;

    private Produit $produitA;

    private Produit $produitB;

    private Adresse $adresse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bastos = $this->creerQuartier('Bastos', 'yaoundé');
        $this->akwa = $this->creerQuartier('Akwa', 'douala');

        // A livre Yaoundé et Douala, B seulement Yaoundé
        $this->vendeurA = $this->creerUtilisateur('vendeur', ['nom_complet' => 'Vendeur A']);
        $this->vendeurB = $this->creerUtilisateur('vendeur', ['nom_complet' => 'Vendeur B']);
        $this->livrerVille($this->vendeurA, 'yaoundé');
        $this->livrerVille($this->vendeurA, 'douala');
        $this->livrerVille($this->vendeurB, 'yaoundé');

        $this->client = $this->creerUtilisateur('client');
        $categorie = $this->creerCategorie(['nom' => 'Gâteaux']);
        $this->produitA = $this->creerProduit($this->vendeurA, ['categorie_id' => $categorie->id, 'nom' => 'Fraisier', 'prix_unitaire' => 5000]);
        $this->produitB = $this->creerProduit($this->vendeurB, ['categorie_id' => $categorie->id, 'nom' => 'Croissant', 'prix_unitaire' => 1000]);

        $this->adresse = $this->creerAdresse($this->client, $this->bastos);
    }

    public function test_le_checkout_cree_une_commande_par_vendeur_avec_les_frais_standard(): void
    {
        $this->remplirPanier();

        $this->postJson('/api/v1/commandes', $this->payloadLivraison())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $commandeA = Commande::where('vendeur_id', $this->vendeurA->id)->firstOrFail();
        $this->assertEquals(10000, (float) $commandeA->montant_produits);
        $this->assertEquals(1500, (float) $commandeA->montant_livraison);
        $this->assertEquals(11500, (float) $commandeA->montant_total);

        $commandeB = Commande::where('vendeur_id', $this->vendeurB->id)->firstOrFail();
        $this->assertEquals(3000, (float) $commandeB->montant_produits);
        $this->assertEquals(4500, (float) $commandeB->montant_total);

        $this->assertDatabaseCount('ligne_commandes', 2);
        $this->assertDatabaseMissing('paniers', ['user_id' => $this->client->id]);
        $this->assertSame(8, $this->produitA->fresh()->stock_disponible);
        $this->assertSame(7, $this->produitB->fresh()->stock_disponible);
    }

    public function test_le_checkout_est_refuse_si_un_vendeur_ne_livre_pas_la_ville(): void
    {
        $adresseDouala = $this->creerAdresse($this->client, $this->akwa);
        $this->remplirPanier();

        $response = $this->postJson('/api/v1/commandes', ['adresse_livraison_id' => $adresseDouala->id] + $this->payloadLivraison());

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertStringContainsString('« Vendeur B » ne livre pas à Douala', $response->json('message'));
        $this->assertDatabaseCount('commandes', 0);
        $this->assertDatabaseCount('paniers', 2);
        $this->assertSame(10, $this->produitA->fresh()->stock_disponible);
    }

    public function test_le_retrait_en_boutique_ne_depend_pas_de_la_ville(): void
    {
        $this->vendeurB->villesLivraison()->delete();
        $this->remplirPanier();

        $this->postJson('/api/v1/commandes', [
            'type_livraison' => 'retrait_boutique',
            'moyen_paiement' => 'especes',
        ])->assertCreated()->assertJsonCount(2, 'data');

        $this->assertSame(0, Commande::where('montant_livraison', '>', 0)->count());
    }

    public function test_changer_l_adresse_d_une_commande_reverifie_la_ville(): void
    {
        $adresseDouala = $this->creerAdresse($this->client, $this->akwa);

        Sanctum::actingAs($this->client);
        $this->postJson('/api/v1/panier', ['produit_id' => $this->produitB->id, 'quantite' => 1])->assertCreated();
        $this->postJson('/api/v1/commandes', $this->payloadLivraison())->assertCreated();
        $commande = Commande::firstOrFail();

        $this->putJson("/api/v1/commandes/{$commande->id}", ['adresse_livraison_id' => $adresseDouala->id])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertSame($this->adresse->id, (int) $commande->fresh()->adresse_livraison_id);
    }

    public function test_le_catalogue_ne_propose_que_les_produits_livrables_dans_la_ville(): void
    {
        $this->getJson('/api/v1/produits')->assertJsonPath('data.total', 2);

        $this->assertSame(['Croissant', 'Fraisier'], collect($this->getJson('/api/v1/produits?ville=yaoundé')->json('data.data'))->pluck('nom')->sort()->values()->all());
        $this->assertSame(['Fraisier'], collect($this->getJson('/api/v1/produits?ville=douala')->json('data.data'))->pluck('nom')->all());

        $this->getJson('/api/v1/produits?ville=bafoussam')->assertStatus(422)->assertJsonValidationErrors('ville');
        $this->getJson('/api/v1/produits/nouveautes?ville=douala')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/categories?ville=douala')->assertJsonPath('data.0.produits_disponibles_count', 1);
    }

    public function test_les_conditions_de_livraison_d_un_vendeur_sont_publiques(): void
    {
        $this->vendeurA->update(['montant_minimum_livraison' => 8000]);

        $this->getJson("/api/v1/livraison/vendeur/{$this->vendeurA->id}")
            ->assertOk()
            ->assertJsonPath('data.frais_livraison', 1500)
            ->assertJsonPath('data.montant_minimum_livraison', 8000)
            ->assertJsonPath('data.villes', ['douala', 'yaoundé']);

        $this->getJson('/api/v1/livraison/quartiers?ville=douala')
            ->assertOk()
            ->assertJsonCount(1, 'data.douala')
            ->assertJsonCount(0, 'data.yaoundé');
    }

    public function test_le_vendeur_choisit_ses_villes_et_son_minimum(): void
    {
        Sanctum::actingAs($this->vendeurB);

        $this->putJson('/api/v1/vendeur/livraison', ['montant_minimum_livraison' => 2000, 'villes' => ['bafoussam']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('villes.0');

        $this->putJson('/api/v1/vendeur/livraison', ['montant_minimum_livraison' => 2000, 'villes' => ['douala']])
            ->assertOk()
            ->assertJsonPath('data.villes', ['douala'])
            ->assertJsonPath('data.montant_minimum_livraison', 2000);

        $this->getJson('/api/v1/vendeur/livraison')->assertJsonPath('data.villes', ['douala']);
        $this->assertFalse($this->vendeurB->fresh()->livreDans('yaoundé'));

        // Les réglages d'un vendeur ne touchent pas ceux des autres
        $this->assertSame(['douala', 'yaoundé'], $this->vendeurA->fresh()->villesLivrees());

        Sanctum::actingAs($this->client);
        $this->putJson('/api/v1/vendeur/livraison', ['montant_minimum_livraison' => 0, 'villes' => []])->assertForbidden();
    }

    public function test_une_adresse_exige_une_ville_et_un_quartier_de_cette_ville(): void
    {
        Sanctum::actingAs($this->client);

        $this->postJson('/api/v1/adresses', ['telephone_contact' => '+237690000003'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ville', 'quartier_id']);

        // Quartier d'une autre ville
        $this->postJson('/api/v1/adresses', [
            'ville' => 'yaoundé',
            'quartier_id' => $this->akwa->id,
            'telephone_contact' => '+237690000003',
        ])->assertStatus(422)->assertJsonValidationErrors('quartier_id');

        $this->postJson('/api/v1/adresses', [
            'libelle' => 'Bureau',
            'ville' => 'douala',
            'quartier_id' => $this->akwa->id,
            'zone' => 'Rue Joss, face à la pharmacie',
            'telephone_contact' => '+237690000003',
        ])
            ->assertCreated()
            ->assertJsonPath('data.quartier', 'Akwa')
            ->assertJsonPath('data.ville', 'douala')
            ->assertJsonPath('data.zone', 'Rue Joss, face à la pharmacie')
            ->assertJsonPath('data.quartier_livraison.id', $this->akwa->id);

        // Modifier seulement la zone garde le quartier ; un libellé vide est accepté
        $this->putJson("/api/v1/adresses/{$this->adresse->id}", ['libelle' => null, 'zone' => 'Carrefour Bastos'])
            ->assertOk()
            ->assertJsonPath('data.quartier', 'Bastos')
            ->assertJsonPath('data.zone', 'Carrefour Bastos')
            ->assertJsonPath('data.libelle', null);
    }

    private function remplirPanier(): void
    {
        Sanctum::actingAs($this->client);

        $this->postJson('/api/v1/panier', ['produit_id' => $this->produitA->id, 'quantite' => 2])->assertCreated();
        $this->postJson('/api/v1/panier', ['produit_id' => $this->produitB->id, 'quantite' => 3])->assertCreated();
    }

    private function payloadLivraison(): array
    {
        return [
            'type_livraison' => 'livraison',
            'adresse_livraison_id' => $this->adresse->id,
            'telephone_livraison' => '+237690000003',
            'date_livraison_souhaitee' => now()->addDay()->toDateString(),
            'heure_livraison_souhaitee' => '10:00',
            'moyen_paiement' => 'especes',
        ];
    }
}
