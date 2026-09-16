<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\LigneCommande;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class IntegriteEtSecuriteTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    public function test_un_produit_deja_commande_ne_peut_pas_etre_supprime(): void
    {
        $vendeur = $this->creerUtilisateur('vendeur');
        $produit = $this->creerProduit($vendeur);
        $commande = $this->creerCommande($this->creerUtilisateur('client'), $vendeur);
        LigneCommande::create([
            'commande_id' => $commande->id,
            'produit_id' => $produit->id,
            'nom_produit' => $produit->nom,
            'quantite' => 1,
            'prix_unitaire' => 1000,
            'sous_total' => 1000,
        ]);

        Sanctum::actingAs($vendeur);

        $this->deleteJson("/api/v1/vendeur/catalogue/produits/{$produit->id}")
            ->assertStatus(422)
            ->assertJsonPath('peut_desactiver', true);

        $this->assertDatabaseHas('produits', ['id' => $produit->id]);
        $this->assertDatabaseCount('ligne_commandes', 1);
    }

    public function test_une_categorie_contenant_des_produits_ne_peut_pas_etre_supprimee(): void
    {
        $admin = $this->creerUtilisateur('admin');
        $categorie = $this->creerCategorie();
        $this->creerProduit($this->creerUtilisateur('vendeur'), ['categorie_id' => $categorie->id]);
        $vide = $this->creerCategorie();

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/admin/categories/{$categorie->id}")->assertStatus(422);
        $this->assertDatabaseCount('produits', 1);

        $this->deleteJson("/api/v1/admin/categories/{$vide->id}")->assertOk();
    }

    public function test_le_checkout_reverifie_stock_disponibilite_et_prix(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $produit = $this->creerProduit($vendeur, ['prix_unitaire' => 1000, 'stock_disponible' => 5]);

        Sanctum::actingAs($client);
        $this->postJson('/api/v1/panier', ['produit_id' => $produit->id, 'quantite' => 3])->assertCreated();

        $retrait = ['type_livraison' => 'retrait_boutique', 'moyen_paiement' => 'especes'];

        // Stock vendu entre-temps à quelqu'un d'autre
        $produit->update(['stock_disponible' => 2]);
        $this->postJson('/api/v1/commandes', $retrait)
            ->assertStatus(422)
            ->assertJsonPath('message', "Stock insuffisant pour « {$produit->nom} » : il en reste 2.");

        // Produit retiré de la vente
        $produit->update(['stock_disponible' => 5, 'est_disponible' => false]);
        $this->postJson('/api/v1/commandes', $retrait)->assertStatus(422);

        // Prix modifié depuis l'ajout au panier : c'est le prix actuel qui est facturé
        $produit->update(['est_disponible' => true, 'prix_unitaire' => 1200]);
        $this->postJson('/api/v1/commandes', $retrait)->assertCreated();

        $commande = Commande::firstOrFail();
        $this->assertEquals(3600, (float) $commande->montant_produits);
        $this->assertSame(2, $produit->fresh()->stock_disponible);
        $this->assertSame(1, $produit->fresh()->nombre_commandes);
    }

    public function test_les_produits_d_un_vendeur_suspendu_ne_peuvent_pas_etre_commandes(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $produit = $this->creerProduit($vendeur);

        Sanctum::actingAs($client);
        $this->postJson('/api/v1/panier', ['produit_id' => $produit->id, 'quantite' => 1])->assertCreated();

        $vendeur->update(['statut' => 'suspendu']);

        $this->postJson('/api/v1/commandes', ['type_livraison' => 'retrait_boutique', 'moyen_paiement' => 'especes'])
            ->assertStatus(422);
        $this->assertDatabaseCount('commandes', 0);
    }

    public function test_une_annulation_par_le_vendeur_remet_le_stock(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $produit = $this->creerProduit($vendeur, ['stock_disponible' => 5]);

        Sanctum::actingAs($client);
        $this->postJson('/api/v1/panier', ['produit_id' => $produit->id, 'quantite' => 2])->assertCreated();
        $this->postJson('/api/v1/commandes', ['type_livraison' => 'retrait_boutique', 'moyen_paiement' => 'especes'])
            ->assertCreated();
        $this->assertSame(3, $produit->fresh()->stock_disponible);

        $commande = Commande::firstOrFail();
        Sanctum::actingAs($vendeur);
        $this->patchJson("/api/v1/vendeur/commandes/{$commande->id}/status", ['statut' => 'annulee'])->assertOk();

        $this->assertSame(5, $produit->fresh()->stock_disponible);
    }

    public function test_les_numeros_de_commande_restent_uniques_apres_une_suppression(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');

        $premiere = $this->creerCommande($client, $vendeur);
        $seconde = $this->creerCommande($client, $vendeur);
        $premiere->delete();

        $troisieme = $this->creerCommande($client, $vendeur);

        $this->assertNotSame($seconde->numero_commande, $troisieme->numero_commande);
        $this->assertStringEndsWith('-0003', $troisieme->numero_commande);
    }

    public function test_la_connexion_est_limitee_contre_la_force_brute(): void
    {
        $client = $this->creerUtilisateur('client');

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', ['telephone' => $client->telephone, 'mot_de_passe' => 'mauvais'])
                ->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', ['telephone' => $client->telephone, 'mot_de_passe' => 'password123'])
            ->assertStatus(429)
            ->assertJsonPath('success', false);
    }

    public function test_l_api_n_utilise_pas_le_middleware_stateful_de_sanctum(): void
    {
        // Avec ce middleware, le SPA servi depuis un domaine "stateful" recevait des 419 (CSRF)
        $groupeApi = app(Kernel::class)->getMiddlewareGroups()['api'] ?? [];

        $this->assertNotContains(EnsureFrontendRequestsAreStateful::class, $groupeApi);
    }
}
