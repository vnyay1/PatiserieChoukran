<?php

namespace Tests\Feature;

use App\Models\Adresse;
use App\Models\Categorie;
use App\Models\Commande;
use App\Models\Produit;
use App\Models\Quartier;
use App\Models\User;
use App\Models\VendeurTarifLivraison;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutMultiVendeurTest extends TestCase
{
    use RefreshDatabase;

    private Quartier $bastos;

    private Quartier $essos;

    private User $vendeurA;

    private User $vendeurB;

    private User $client;

    private Produit $produitA;

    private Produit $produitB;

    private VendeurTarifLivraison $tarifA;

    private VendeurTarifLivraison $tarifB;

    private Adresse $adresse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bastos = Quartier::create(['nom' => 'Bastos', 'ville' => 'yaoundé', 'actif' => true]);
        $this->essos = Quartier::create(['nom' => 'Essos', 'ville' => 'yaoundé', 'actif' => true]);

        $this->vendeurA = $this->creerUtilisateur('Vendeur A', '+237690000001', 'vendeur');
        $this->vendeurB = $this->creerUtilisateur('Vendeur B', '+237690000002', 'vendeur');
        $this->client = $this->creerUtilisateur('Client Test', '+237690000003', 'client');

        $categorie = Categorie::create(['nom' => 'Gâteaux', 'slug' => 'gateaux']);

        $this->produitA = Produit::create([
            'categorie_id' => $categorie->id,
            'created_by_user_id' => $this->vendeurA->id,
            'nom' => 'Fraisier',
            'prix_unitaire' => 5000,
            'stock_disponible' => 10,
            'est_disponible' => true,
        ]);

        $this->produitB = Produit::create([
            'categorie_id' => $categorie->id,
            'created_by_user_id' => $this->vendeurB->id,
            'nom' => 'Croissant',
            'prix_unitaire' => 1000,
            'stock_disponible' => 10,
            'est_disponible' => true,
        ]);

        $this->tarifA = VendeurTarifLivraison::create([
            'vendeur_id' => $this->vendeurA->id,
            'quartier_id' => $this->bastos->id,
            'delai_min' => 30,
            'delai_max' => 60,
            'actif' => true,
        ]);

        $this->tarifB = VendeurTarifLivraison::create([
            'vendeur_id' => $this->vendeurB->id,
            'quartier_id' => $this->bastos->id,
            'delai_min' => 45,
            'delai_max' => 90,
            'actif' => true,
        ]);

        $this->adresse = Adresse::create([
            'user_id' => $this->client->id,
            'libelle' => 'Maison',
            'quartier' => 'Bastos',
            'ville' => 'yaoundé',
            'quartier_id' => $this->bastos->id,
            'telephone_contact' => '+237690000003',
            'est_principale' => true,
        ]);
    }

    public function test_le_checkout_cree_une_commande_par_vendeur_avec_les_frais_standard(): void
    {
        $this->remplirPanier();

        $this->assertDatabaseHas('paniers', ['produit_id' => $this->produitA->id, 'vendeur_id' => $this->vendeurA->id]);
        $this->assertDatabaseHas('paniers', ['produit_id' => $this->produitB->id, 'vendeur_id' => $this->vendeurB->id]);

        $response = $this->postJson('/api/v1/commandes', $this->payloadLivraison());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $commandeA = Commande::where('vendeur_id', $this->vendeurA->id)->firstOrFail();
        $this->assertEquals(10000, (float) $commandeA->montant_produits);
        $this->assertEquals(1500, (float) $commandeA->montant_livraison);
        $this->assertEquals(11500, (float) $commandeA->montant_total);
        $this->assertSame($this->vendeurA->id, (int) $commandeA->livreur_id);

        $commandeB = Commande::where('vendeur_id', $this->vendeurB->id)->firstOrFail();
        $this->assertEquals(3000, (float) $commandeB->montant_produits);
        $this->assertEquals(1500, (float) $commandeB->montant_livraison);
        $this->assertEquals(4500, (float) $commandeB->montant_total);

        $this->assertDatabaseCount('ligne_commandes', 2);
        $this->assertDatabaseCount('historique_statut_commandes', 2);
        $this->assertDatabaseMissing('paniers', ['user_id' => $this->client->id]);
        $this->assertSame(8, $this->produitA->fresh()->stock_disponible);
        $this->assertSame(7, $this->produitB->fresh()->stock_disponible);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->vendeurA->id, 'type' => 'commande']);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->vendeurB->id, 'type' => 'commande']);
    }

    public function test_le_checkout_est_refuse_si_un_vendeur_ne_livre_pas_le_quartier(): void
    {
        $this->tarifB->delete();
        $this->remplirPanier();

        $response = $this->postJson('/api/v1/commandes', $this->payloadLivraison());

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertStringContainsString('ne livre pas dans votre quartier', $response->json('message'));

        $this->assertDatabaseCount('commandes', 0);
        $this->assertDatabaseCount('paniers', 2);
        $this->assertSame(10, $this->produitA->fresh()->stock_disponible);
    }

    public function test_le_retrait_en_boutique_cree_les_commandes_sans_frais(): void
    {
        $this->remplirPanier();

        $response = $this->postJson('/api/v1/commandes', [
            'type_livraison' => 'retrait_boutique',
            'moyen_paiement' => 'especes',
        ]);

        $response->assertCreated()->assertJsonCount(2, 'data');
        $this->assertSame(0, Commande::where('montant_livraison', '>', 0)->count());
        $this->assertSame(0, Commande::whereNotNull('adresse_livraison_id')->count());
    }

    public function test_la_modification_d_une_commande_verifie_la_desserte_du_nouveau_quartier(): void
    {
        VendeurTarifLivraison::create([
            'vendeur_id' => $this->vendeurA->id,
            'quartier_id' => $this->essos->id,
            'delai_min' => 30,
            'delai_max' => 60,
            'actif' => true,
        ]);

        $adresseEssos = Adresse::create([
            'user_id' => $this->client->id,
            'quartier' => 'Essos',
            'ville' => 'yaoundé',
            'quartier_id' => $this->essos->id,
            'telephone_contact' => '+237690000003',
        ]);

        $mvogMbi = Quartier::create(['nom' => 'Mvog-Mbi', 'ville' => 'yaoundé', 'actif' => true]);
        $adresseNonDesservie = Adresse::create([
            'user_id' => $this->client->id,
            'quartier' => 'Mvog-Mbi',
            'ville' => 'yaoundé',
            'quartier_id' => $mvogMbi->id,
            'telephone_contact' => '+237690000003',
        ]);

        Sanctum::actingAs($this->client);
        $this->postJson('/api/v1/panier', ['produit_id' => $this->produitA->id, 'quantite' => 1])->assertCreated();
        $this->postJson('/api/v1/commandes', $this->payloadLivraison())->assertCreated();

        $commande = Commande::firstOrFail();

        $this->putJson("/api/v1/commandes/{$commande->id}", [
            'adresse_livraison_id' => $adresseEssos->id,
        ])->assertOk()->assertJsonPath('success', true);

        $commande->refresh();
        $this->assertSame($adresseEssos->id, (int) $commande->adresse_livraison_id);
        $this->assertEquals(1500, (float) $commande->montant_livraison);
        $this->assertEquals(6500, (float) $commande->montant_total);

        // Quartier que le vendeur ne dessert pas : refus, la commande ne change pas
        $this->putJson("/api/v1/commandes/{$commande->id}", [
            'adresse_livraison_id' => $adresseNonDesservie->id,
        ])->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame($adresseEssos->id, (int) $commande->fresh()->adresse_livraison_id);
    }

    public function test_le_json_d_adresse_garde_le_nom_du_quartier(): void
    {
        Sanctum::actingAs($this->client);

        $this->postJson('/api/v1/adresses', ['telephone_contact' => '+237690000003'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quartier_id');

        $this->postJson('/api/v1/adresses', [
            'libelle' => 'Bureau',
            'quartier_id' => $this->essos->id,
            'telephone_contact' => '+237690000003',
        ])
            ->assertCreated()
            ->assertJsonPath('data.quartier', 'Essos')
            ->assertJsonPath('data.ville', 'yaoundé')
            ->assertJsonPath('data.quartier_livraison.id', $this->essos->id);

        $this->getJson('/api/v1/adresses')
            ->assertOk()
            ->assertJsonPath('data.0.quartier', 'Bastos');

        // Changer de quartier met à jour le nom et la ville ; un libellé vide est accepté
        $this->putJson("/api/v1/adresses/{$this->adresse->id}", [
            'libelle' => null,
            'quartier_id' => $this->essos->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.quartier', 'Essos')
            ->assertJsonPath('data.libelle', null);
    }

    public function test_la_livraison_d_un_vendeur_est_publique(): void
    {
        $this->tarifB->update(['actif' => false]);
        $this->vendeurA->update(['montant_minimum_livraison' => 8000]);

        $this->getJson("/api/v1/livraison/quartiers/vendeur/{$this->vendeurA->id}")
            ->assertOk()
            ->assertJsonPath('data.frais_livraison', 1500)
            ->assertJsonPath('data.montant_minimum_livraison', 8000)
            ->assertJsonCount(1, 'data.quartiers')
            ->assertJsonPath('data.quartiers.0.nom', 'Bastos')
            ->assertJsonPath('data.quartiers.0.delai_max', 60);

        $this->getJson("/api/v1/livraison/quartiers/vendeur/{$this->vendeurB->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data.quartiers');
    }

    public function test_un_vendeur_ne_peut_pas_modifier_la_desserte_d_un_autre_vendeur(): void
    {
        Sanctum::actingAs($this->vendeurB);

        $this->putJson("/api/v1/vendeur/tarifs-livraison/{$this->tarifA->id}", ['delai_max' => 120])->assertNotFound();
        $this->deleteJson("/api/v1/vendeur/tarifs-livraison/{$this->tarifA->id}")->assertNotFound();
        $this->assertSame(60, $this->tarifA->fresh()->delai_max);

        Sanctum::actingAs($this->vendeurA);

        $this->putJson("/api/v1/vendeur/tarifs-livraison/{$this->tarifA->id}", ['delai_max' => 120])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertSame(120, $this->tarifA->fresh()->delai_max);
    }

    // Les vendeurs ont un profil boutique complet, sans quoi leurs produits seraient masqués
    private function creerUtilisateur(string $nom, string $telephone, string $role): User
    {
        $profilBoutique = $role === 'vendeur' ? [
            'email' => 'vendeur'.substr($telephone, -3).'@exemple.cm',
            'logo_boutique' => 'boutiques/logo.png',
            'description_boutique' => 'Pâtisserie artisanale de test, gâteaux et viennoiseries.',
            'conditions_acceptees_le' => now(),
        ] : [];

        return User::create([
            'nom_complet' => $nom,
            'telephone' => $telephone,
            'mot_de_passe' => 'password123',
            'role' => $role,
            'statut' => 'actif',
            ...$profilBoutique,
        ]);
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
