<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_un_creneau_de_livraison_deja_passe_est_refuse(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 15:00'));

        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $quartier = $this->creerQuartier();
        $adresse = $this->creerAdresse($client, $quartier);
        $this->livrerVille($vendeur, $quartier->ville);
        $produit = $this->creerProduit($vendeur);

        Sanctum::actingAs($client);
        $this->postJson('/api/v1/panier', ['produit_id' => $produit->id, 'quantite' => 1])->assertCreated();

        $payload = [
            'type_livraison' => 'livraison',
            'adresse_livraison_id' => $adresse->id,
            'telephone_livraison' => $client->telephone,
            'date_livraison_souhaitee' => '2026-09-11',
            'moyen_paiement' => 'especes',
        ];

        // Aujourd'hui mais à une heure déjà passée
        $this->postJson('/api/v1/commandes', $payload + ['heure_livraison_souhaitee' => '10:00'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ce créneau de livraison est déjà passé : choisissez une heure à venir.');

        // Aujourd'hui, plus tard dans la journée : accepté
        $this->postJson('/api/v1/commandes', $payload + ['heure_livraison_souhaitee' => '17:00'])
            ->assertCreated()
            ->assertJsonPath('data.0.date_livraison_souhaitee', '2026-09-11');

        Carbon::setTestNow();
    }

    public function test_modifier_une_commande_sans_changer_l_heure_reste_possible(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $commande = $this->creerCommande($client, $vendeur, [
            'date_livraison_souhaitee' => now()->addDays(2)->toDateString(),
            'heure_livraison_souhaitee' => '14:30:00',
        ]);

        Sanctum::actingAs($client);

        $this->putJson("/api/v1/commandes/{$commande->id}", [
            'date_livraison_souhaitee' => $commande->date_livraison_souhaitee->format('Y-m-d'),
            'heure_livraison_souhaitee' => '14:30',
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
