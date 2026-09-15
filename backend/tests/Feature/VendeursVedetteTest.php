<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class VendeursVedetteTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    public function test_l_admin_met_un_vendeur_en_vedette(): void
    {
        $admin = $this->creerUtilisateur('admin');
        $vendeur = $this->creerUtilisateur('vendeur');
        $client = $this->creerUtilisateur('client');

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/users/{$vendeur->id}/vedette", ['est_vendeur_vedette' => true])
            ->assertOk()
            ->assertJsonPath('data.est_vendeur_vedette', true);

        $this->patchJson("/api/v1/admin/users/{$client->id}/vedette", ['est_vendeur_vedette' => true])
            ->assertStatus(422);

        $this->getJson('/api/v1/admin/users?vedette=1')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $vendeur->id);

        // Un vendeur rétrogradé perd sa mise en avant
        $this->patchJson("/api/v1/admin/users/{$vendeur->id}/role", ['role' => 'client'])->assertOk();
        $this->assertFalse($vendeur->fresh()->est_vendeur_vedette);
    }

    public function test_seul_l_admin_choisit_les_vendeurs_vedettes(): void
    {
        $vendeur = $this->creerUtilisateur('vendeur');
        Sanctum::actingAs($vendeur);

        $this->patchJson("/api/v1/admin/users/{$vendeur->id}/vedette", ['est_vendeur_vedette' => true])->assertForbidden();
        $this->assertFalse((bool) $vendeur->fresh()->est_vendeur_vedette);
    }

    public function test_les_produits_des_vendeurs_vedettes_sortent_en_tete_quel_que_soit_le_tri(): void
    {
        $vedette = $this->creerUtilisateur('vendeur', ['est_vendeur_vedette' => true]);
        $standard = $this->creerUtilisateur('vendeur');
        $categorie = $this->creerCategorie();

        $this->creerProduit($standard, ['categorie_id' => $categorie->id, 'nom' => 'Beignet', 'prix_unitaire' => 200]);
        $this->creerProduit($vedette, ['categorie_id' => $categorie->id, 'nom' => 'Fraisier', 'prix_unitaire' => 15000]);
        $this->creerProduit($standard, ['categorie_id' => $categorie->id, 'nom' => 'Croissant', 'prix_unitaire' => 500]);

        $reponse = $this->getJson('/api/v1/produits?sort_by=prix&sort_order=asc')->assertOk();

        $this->assertSame(['Fraisier', 'Beignet', 'Croissant'], $reponse->json('data.data.*.nom'));
        $reponse->assertJsonPath('data.data.0.createur.est_vendeur_vedette', true)
            ->assertJsonMissingPath('data.data.0.createur.telephone');

        $this->getJson('/api/v1/produits/featured')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nom', 'Fraisier');

        $this->getJson('/api/v1/produits?vedette=1')->assertJsonPath('data.total', 1);

        $this->getJson("/api/v1/categories/{$categorie->slug}")
            ->assertOk()
            ->assertJsonPath('data.produits_disponibles.0.nom', 'Fraisier');
    }
}
