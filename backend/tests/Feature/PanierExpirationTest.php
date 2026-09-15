<?php

namespace Tests\Feature;

use App\Models\Panier;
use App\Models\ParametreSite;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class PanierExpirationTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    private User $client;

    private Produit $gateau;

    private Produit $glace;

    protected function setUp(): void
    {
        parent::setUp();

        ParametreSite::set('panier_duree_minutes', 60, 'integer');
        $vendeur = $this->creerUtilisateur('vendeur');
        $this->client = $this->creerUtilisateur('client');
        $this->gateau = $this->creerProduit($vendeur, ['nom' => 'Gâteau']);
        $this->glace = $this->creerProduit($vendeur, ['nom' => 'Glace']);
    }

    public function test_le_panier_entier_se_vide_apres_la_duree_sans_modification(): void
    {
        Sanctum::actingAs($this->client);
        $this->postJson('/api/v1/panier', ['produit_id' => $this->gateau->id, 'quantite' => 1])
            ->assertCreated()
            ->assertJsonPath('data.nombre_items', 1)
            ->assertJsonPath('data.duree_minutes', 60);

        $this->travel(40)->minutes();
        // Ajouter un article prolonge tout le panier, y compris le gâteau ajouté plus tôt
        $this->postJson('/api/v1/panier', ['produit_id' => $this->glace->id, 'quantite' => 2])->assertCreated();

        $this->travel(40)->minutes();
        $this->getJson('/api/v1/panier')->assertOk()->assertJsonPath('data.nombre_items', 2);

        $this->travel(21)->minutes();
        $this->getJson('/api/v1/panier')
            ->assertOk()
            ->assertJsonPath('data.nombre_items', 0)
            ->assertJsonPath('data.expire_le', null);
        $this->assertDatabaseCount('paniers', 0);
    }

    public function test_l_heure_d_expiration_est_renvoyee_et_suit_le_reglage_de_l_admin(): void
    {
        $this->travelTo(now()->startOfMinute());

        Sanctum::actingAs($this->client);
        $reponse = $this->postJson('/api/v1/panier', ['produit_id' => $this->gateau->id, 'quantite' => 1])->assertCreated();
        $this->assertSame(now()->addMinutes(60)->toIso8601String(), $reponse->json('data.expire_le'));

        // Réglage raccourci : il s'applique aussitôt aux paniers existants
        ParametreSite::set('panier_duree_minutes', 10, 'integer');
        $this->travel(11)->minutes();

        $this->getJson('/api/v1/panier/count')->assertJsonPath('data.count', 0);
    }

    public function test_la_commande_planifiee_vide_les_paniers_abandonnes_de_tous_les_clients(): void
    {
        $autreClient = $this->creerUtilisateur('client');
        Panier::create(['user_id' => $this->client->id, 'produit_id' => $this->gateau->id, 'quantite' => 1, 'prix_unitaire_actuel' => 1000]);

        $this->travel(2)->hours();
        Panier::create(['user_id' => $autreClient->id, 'produit_id' => $this->glace->id, 'quantite' => 1, 'prix_unitaire_actuel' => 1000]);

        $this->artisan('panier:purge-expired')->assertSuccessful();

        $this->assertDatabaseMissing('paniers', ['user_id' => $this->client->id]);
        $this->assertDatabaseHas('paniers', ['user_id' => $autreClient->id]);
    }

    public function test_l_admin_regle_la_duree_du_panier_et_les_frais_de_livraison(): void
    {
        Sanctum::actingAs($this->creerUtilisateur('admin'));

        $this->getJson('/api/v1/admin/reglages')
            ->assertOk()
            ->assertJsonPath('data.panier_duree_minutes', 60)
            ->assertJsonPath('data.frais_livraison_standard', 1500);

        $this->putJson('/api/v1/admin/reglages', ['panier_duree_minutes' => 2])
            ->assertStatus(422)
            ->assertJsonValidationErrors('panier_duree_minutes');

        $this->putJson('/api/v1/admin/reglages', ['panier_duree_minutes' => 180, 'frais_livraison_standard' => 2000])
            ->assertOk()
            ->assertJsonPath('data.panier_duree_minutes', 180)
            ->assertJsonPath('data.frais_livraison_standard', 2000);

        $this->assertSame(180, Panier::dureeMinutes());

        Sanctum::actingAs($this->client);
        $this->putJson('/api/v1/admin/reglages', ['panier_duree_minutes' => 5])->assertForbidden();
    }
}
