<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class CommandesClientTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    public function test_le_filtre_en_cours_regroupe_les_commandes_non_terminees(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $this->creerCommande($client, $vendeur, ['statut' => 'en_attente']);
        $this->creerCommande($client, $vendeur, ['statut' => 'en_preparation']);
        $this->creerCommande($client, $vendeur, ['statut' => 'livree']);
        $this->creerCommande($client, $vendeur, ['statut' => 'annulee']);

        Sanctum::actingAs($client);

        $this->getJson('/api/v1/commandes?statut=en_cours')->assertOk()->assertJsonPath('data.total', 2);
        $this->getJson('/api/v1/commandes?statut=livree')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson('/api/v1/commandes')->assertOk()->assertJsonPath('data.total', 4);
    }

    public function test_la_livraison_se_commande_sans_date_ni_heure(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $quartier = $this->creerQuartier();
        $adresse = $this->creerAdresse($client, $quartier);
        $this->livrerVille($vendeur, $quartier->ville);
        $produit = $this->creerProduit($vendeur);

        Sanctum::actingAs($client);
        $this->postJson('/api/v1/panier', ['produit_id' => $produit->id, 'quantite' => 1])->assertCreated();

        // Une date ou une heure envoyée par un ancien client est simplement ignorée
        $this->postJson('/api/v1/commandes', [
            'type_livraison' => 'livraison',
            'adresse_livraison_id' => $adresse->id,
            'telephone_livraison' => $client->telephone,
            'date_livraison_souhaitee' => '2020-01-01',
            'heure_livraison_souhaitee' => '23:00',
            'moyen_paiement' => 'especes',
        ])
            ->assertCreated()
            ->assertJsonMissingPath('data.0.date_livraison_souhaitee')
            ->assertJsonMissingPath('data.0.heure_livraison_souhaitee');
    }

    public function test_le_client_modifie_les_instructions_d_une_commande_en_attente(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $commande = $this->creerCommande($client, $vendeur);

        Sanctum::actingAs($client);

        $this->putJson("/api/v1/commandes/{$commande->id}", [
            'instructions_speciales' => 'Sonner deux fois',
        ])
            ->assertOk()
            ->assertJsonPath('data.instructions_speciales', 'Sonner deux fois')
            ->assertJsonPath('data.vendeur.id', $vendeur->id);
    }

    public function test_le_panier_verifie_le_stock_cumule(): void
    {
        $client = $this->creerUtilisateur('client');
        $produit = $this->creerProduit($this->creerUtilisateur('vendeur'), ['stock_disponible' => 5]);

        Sanctum::actingAs($client);

        $this->postJson('/api/v1/panier', ['produit_id' => $produit->id, 'quantite' => 4])->assertCreated();

        $this->postJson('/api/v1/panier', ['produit_id' => $produit->id, 'quantite' => 3])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Stock insuffisant : vous pouvez encore ajouter 1 unité(s) de ce produit.');

        $this->postJson('/api/v1/panier', ['produit_id' => $produit->id, 'quantite' => 1])->assertCreated();
        $this->assertDatabaseHas('paniers', ['produit_id' => $produit->id, 'quantite' => 5]);
    }
}
