<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class ProfilVendeurTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    public function test_un_vendeur_au_profil_incomplet_est_bloque_hors_du_formulaire(): void
    {
        $vendeur = $this->creerVendeurIncomplet();
        Sanctum::actingAs($vendeur);

        $this->getJson('/api/v1/vendeur/commandes')
            ->assertForbidden()
            ->assertJsonPath('code', 'profil_vendeur_incomplet');
        $this->getJson('/api/v1/vendeur/catalogue/produits')->assertForbidden();

        $this->getJson('/api/v1/vendeur/profil')
            ->assertOk()
            ->assertJsonPath('data.profil.profil_vendeur_complet', false)
            ->assertJsonPath('data.conditions', fn ($conditions) => str_contains($conditions, 'je m\'engage'));

        $this->getJson('/api/v1/auth/user')->assertJsonPath('data.profil_vendeur_complet', false);
    }

    public function test_le_vendeur_complete_son_profil_boutique(): void
    {
        $vendeur = $this->creerVendeurIncomplet();
        Sanctum::actingAs($vendeur);

        $donnees = [
            '_method' => 'PUT',
            'email' => 'boutique@exemple.cm',
            'logo_boutique' => UploadedFile::fake()->image('logo.png', 200, 200),
            'description_boutique' => 'Gâteaux d\'anniversaire et entremets préparés chaque matin à Bastos.',
        ];

        // Les conditions sont obligatoires
        $this->post('/api/v1/vendeur/profil', $donnees, ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('conditions_acceptees');

        $response = $this->post('/api/v1/vendeur/profil', $donnees + ['conditions_acceptees' => '1'], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('data.profil.profil_vendeur_complet', true)
            ->assertJsonPath('data.user.profil_vendeur_complet', true);

        $vendeur->refresh();
        $this->assertSame('boutique@exemple.cm', $vendeur->email);
        $this->assertNotNull($vendeur->conditions_acceptees_le);
        Storage::disk('public')->assertExists($vendeur->logo_boutique);

        $this->getJson('/api/v1/vendeur/commandes')->assertOk();
    }

    public function test_le_logo_et_une_description_suffisante_sont_obligatoires(): void
    {
        Sanctum::actingAs($this->creerVendeurIncomplet());

        $this->post('/api/v1/vendeur/profil', [
            '_method' => 'PUT',
            'email' => 'pas-un-email',
            'description_boutique' => 'Trop court',
            'conditions_acceptees' => '1',
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'logo_boutique', 'description_boutique']);
    }

    public function test_les_produits_d_un_vendeur_incomplet_sont_masques_et_refuses_au_panier(): void
    {
        $incomplet = $this->creerVendeurIncomplet();
        $complet = $this->creerUtilisateur('vendeur');
        $cache = $this->creerProduit($incomplet, ['nom' => 'Caché']);
        $this->creerProduit($complet, ['nom' => 'Visible']);

        $this->getJson('/api/v1/produits')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.nom', 'Visible');
        $this->getJson("/api/v1/produits/{$cache->slug}")->assertNotFound();

        Sanctum::actingAs($this->creerUtilisateur('client'));
        $this->postJson('/api/v1/panier', ['produit_id' => $cache->id, 'quantite' => 1])->assertStatus(400);
    }

    public function test_la_page_publique_d_un_vendeur(): void
    {
        $vendeur = $this->creerUtilisateur('vendeur', [
            'nom_complet' => 'Chez Awa',
            'email' => 'awa@exemple.cm',
            'description_boutique' => 'Glaces artisanales aux fruits de saison, préparées à Douala.',
        ]);
        $this->creerProduit($vendeur);
        $incomplet = $this->creerVendeurIncomplet();

        $this->getJson("/api/v1/vendeurs/{$vendeur->id}")
            ->assertOk()
            ->assertJsonPath('data.nom_complet', 'Chez Awa')
            ->assertJsonPath('data.email', 'awa@exemple.cm')
            ->assertJsonPath('data.logo_boutique', 'boutiques/logo-test.png')
            ->assertJsonPath('data.nombre_produits', 1)
            ->assertJsonMissingPath('data.telephone');

        $this->getJson("/api/v1/vendeurs/{$incomplet->id}")->assertNotFound();
        $this->getJson('/api/v1/vendeurs/'.$this->creerUtilisateur('client')->id)->assertNotFound();

        $this->getJson("/api/v1/produits?vendeur_id={$vendeur->id}")->assertOk()->assertJsonPath('data.total', 1);
    }

    public function test_le_client_promu_vendeur_est_invite_a_completer_son_profil(): void
    {
        $admin = $this->creerUtilisateur('admin');
        $client = $this->creerUtilisateur('client', ['email' => 'futur@exemple.cm']);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/v1/admin/users/{$client->id}/role", ['role' => 'vendeur'])
            ->assertOk()
            ->assertJsonPath('data.profil_vendeur_complet', false);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $client->id,
            'type' => 'compte',
            'url_action' => '/vendeur/profil-boutique',
        ]);
    }

    public function test_un_vendeur_ne_peut_pas_effacer_son_email(): void
    {
        $vendeur = $this->creerUtilisateur('vendeur');
        Sanctum::actingAs($vendeur);

        $this->putJson('/api/v1/auth/profile', ['email' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }
}
