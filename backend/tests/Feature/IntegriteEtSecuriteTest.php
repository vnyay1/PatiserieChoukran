<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\LigneCommande;
use App\Providers\AppServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Artisan;
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

    public function test_un_jeton_de_connexion_expire_apres_30_jours(): void
    {
        $client = $this->creerUtilisateur('client');
        $jeton = $client->createToken('auth_token')->plainTextToken;

        $this->travel(29)->days();
        $this->withToken($jeton)->getJson('/api/v1/auth/user')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->travel(2)->days();
        $this->withToken($jeton)->getJson('/api/v1/auth/user')->assertUnauthorized();
    }

    public function test_les_jetons_expires_sont_purges_chaque_jour(): void
    {
        Artisan::call('schedule:list');

        $this->assertStringContainsString('sanctum:prune-expired', Artisan::output());
    }

    public function test_par_defaut_seul_le_spa_peut_appeler_l_api_depuis_une_autre_origine(): void
    {
        $preflight = fn (string $origine) => $this->call('OPTIONS', '/api/v1/produits', [], [], [], [
            'HTTP_ORIGIN' => $origine,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);

        // Une seule origine autorisée : elle est renvoyée telle quelle et le navigateur bloque
        // toute autre origine (jamais « * » ni l'origine qui demande)
        $autorisee = $preflight('http://pirate.test')->headers->get('Access-Control-Allow-Origin');
        $this->assertNotContains($autorisee, ['*', 'http://pirate.test']);

        $preflight(config('app.frontend_url'))->assertHeader('Access-Control-Allow-Origin', config('app.frontend_url'));
    }

    public function test_derriere_le_proxy_de_production_la_limite_vise_le_vrai_client(): void
    {
        config(['app.proxies_de_confiance' => '*']);
        $this->app->getProvider(AppServiceProvider::class)->boot();

        try {
            $this->epuiserLimiteParIp('198.51.100.1');

            // Un autre client derrière le même proxy (Caddy) n'est pas bloqué
            $this->postJson('/api/v1/auth/login', ['telephone' => '690000999', 'mot_de_passe' => 'mauvais'], ['X-Forwarded-For' => '198.51.100.2'])
                ->assertStatus(422);
        } finally {
            TrustProxies::flushState();
        }
    }

    public function test_sans_proxy_de_confiance_l_en_tete_x_forwarded_for_est_ignore(): void
    {
        $this->epuiserLimiteParIp('198.51.100.1');

        // En-tête falsifié : le client reste identifié par son adresse réelle
        $this->postJson('/api/v1/auth/login', ['telephone' => '690000999', 'mot_de_passe' => 'mauvais'], ['X-Forwarded-For' => '198.51.100.2'])
            ->assertStatus(429);
    }

    // 30 échecs de connexion (numéros différents : seule la limite par adresse IP joue)
    private function epuiserLimiteParIp(string $ip): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->postJson('/api/v1/auth/login', ['telephone' => '6900001'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'mot_de_passe' => 'mauvais'], ['X-Forwarded-For' => $ip])
                ->assertStatus(422);
        }
    }

    public function test_l_api_n_utilise_pas_le_middleware_stateful_de_sanctum(): void
    {
        // Avec ce middleware, le SPA servi depuis un domaine "stateful" recevait des 419 (CSRF)
        $groupeApi = app(Kernel::class)->getMiddlewareGroups()['api'] ?? [];

        $this->assertNotContains(EnsureFrontendRequestsAreStateful::class, $groupeApi);
    }
}
