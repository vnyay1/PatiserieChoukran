<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    public function test_le_filtre_de_prix_porte_sur_le_prix_reellement_paye(): void
    {
        $categorie = $this->creerCategorie();
        $this->creerProduit(null, ['categorie_id' => $categorie->id, 'nom' => 'Cornet', 'prix_unitaire' => 500]);
        $this->creerProduit(null, ['categorie_id' => $categorie->id, 'nom' => 'Fraisier', 'prix_unitaire' => 12000, 'prix_promo' => 9000]);
        $this->creerProduit(null, ['categorie_id' => $categorie->id, 'nom' => 'Pièce montée', 'prix_unitaire' => 50000]);

        $noms = $this->getJson('/api/v1/produits?prix_min=1000&prix_max=10000')
            ->assertOk()
            ->json('data.data.*.nom');

        $this->assertSame(['Fraisier'], $noms);
    }

    public function test_le_tri_refuse_les_colonnes_et_sens_non_autorises(): void
    {
        $this->getJson('/api/v1/produits?sort_by=prix&sort_order='.urlencode('desc, (select 1)'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort_order');

        $this->getJson('/api/v1/produits?sort_by=mot_de_passe')
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort_by');

        $this->getJson('/api/v1/produits?per_page=100000')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');

        $this->getJson('/api/v1/produits?sort_by=prix&sort_order=asc&promotion=true')->assertOk();
    }

    public function test_les_produits_d_une_categorie_desactivee_ne_sont_plus_proposes(): void
    {
        $active = $this->creerCategorie(['nom' => 'Gâteaux']);
        $inactive = $this->creerCategorie(['nom' => 'Archives', 'est_actif' => false]);
        $visible = $this->creerProduit(null, ['categorie_id' => $active->id, 'nom' => 'Visible']);
        $cache = $this->creerProduit(null, ['categorie_id' => $inactive->id, 'nom' => 'Caché']);

        $this->getJson('/api/v1/produits')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson("/api/v1/produits/{$visible->slug}")->assertOk();
        $this->getJson("/api/v1/produits/{$cache->slug}")->assertNotFound();
    }

    public function test_une_image_de_categorie_de_plus_de_2_mo_est_acceptee(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->creerUtilisateur('admin'));

        $reponse = $this->post('/api/v1/admin/categories', [
            'nom' => 'Glaces',
            'est_actif' => 1,
            'ordre_affichage' => '',
            'image' => UploadedFile::fake()->create('glaces.jpg', 3500, 'image/jpeg'),
        ], ['Accept' => 'application/json']);

        $reponse->assertCreated()->assertJsonPath('data.ordre_affichage', 0);
        Storage::disk('public')->assertExists($reponse->json('data.image'));

        // Au-delà de 5 Mo : refus avec un message lisible (et non "validation.max.file")
        $this->post('/api/v1/admin/categories', [
            'nom' => 'Trop lourde',
            'image' => UploadedFile::fake()->create('lourde.jpg', 6000, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('errors.image.0', 'Le fichier image ne doit pas dépasser 5120 kilo-octets.');
    }

    public function test_la_recherche_admin_porte_aussi_sur_la_description(): void
    {
        Sanctum::actingAs($this->creerUtilisateur('admin'));
        $this->creerProduit(null, ['nom' => 'Tarte', 'description' => 'Aux fraises de saison']);
        $this->creerProduit(null, ['nom' => 'Éclair', 'description' => 'Au café']);

        $this->getJson('/api/v1/admin/produits?search=fraises')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.nom', 'Tarte');
    }

    public function test_les_messages_de_validation_sont_en_francais(): void
    {
        $client = $this->creerUtilisateur('client');

        $this->postJson('/api/v1/auth/register', [
            'nom_complet' => 'Doublon',
            'telephone' => $client->telephone,
            'mot_de_passe' => 'secret123',
            'mot_de_passe_confirmation' => 'secret123',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.telephone.0', 'Ce numéro de téléphone est déjà associé à un compte.');
    }
}
