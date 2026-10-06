<?php

namespace Tests\Feature;

use App\Exceptions\RegleMetierException;
use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\Notification;
use App\Models\Paiement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class CycleDeVieCommandeTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    public function test_les_statuts_avancent_etape_par_etape_sans_retour_arriere(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $commande = $this->creerCommande($client, $vendeur, ['type_livraison' => 'livraison']);

        Sanctum::actingAs($vendeur);
        $url = "/api/v1/vendeur/commandes/{$commande->id}/status";

        $this->getJson("/api/v1/vendeur/commandes/{$commande->id}")
            ->assertJsonPath('data.statuts_suivants', ['confirmee', 'annulee']);

        // Sauter des étapes est refusé, avec un message clair
        $this->patchJson($url, ['statut' => 'livree'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "Impossible de passer la commande {$commande->numero_commande} de « En attente » à « Livrée ».");

        foreach (['confirmee', 'en_preparation', 'prete'] as $statut) {
            $this->patchJson($url, ['statut' => $statut])->assertOk();
        }

        // Livraison à domicile : « prête » passe par « en livraison »
        $this->assertSame(['en_livraison', 'annulee'], $commande->fresh()->statuts_suivants);
        $this->patchJson($url, ['statut' => 'livree'])->assertStatus(422);
        $this->patchJson($url, ['statut' => 'en_livraison'])->assertOk();
        $this->patchJson($url, ['statut' => 'livree'])->assertOk();

        // État final : plus aucune transition, ni retour en arrière
        $this->patchJson($url, ['statut' => 'en_attente'])->assertStatus(422);
        $this->assertSame([], $commande->fresh()->statuts_suivants);
    }

    public function test_un_retrait_en_boutique_passe_de_prete_a_livree(): void
    {
        $commande = $this->creerCommande($this->creerUtilisateur('client'), $this->creerUtilisateur('vendeur'), [
            'type_livraison' => 'retrait_boutique',
            'statut' => 'prete',
        ]);

        $this->assertSame(['livree', 'annulee'], $commande->statuts_suivants);
    }

    public function test_le_client_est_notifie_des_etapes_et_du_paiement(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $commande = $this->creerCommande($client, $vendeur);

        Sanctum::actingAs($vendeur);
        $this->patchJson("/api/v1/vendeur/commandes/{$commande->id}/status", [
            'statut' => 'confirmee',
            'commentaire' => 'Prête vers 16h',
        ])->assertOk();

        $notification = Notification::where('user_id', $client->id)
            ->where('titre', "Commande {$commande->numero_commande} : Confirmée")
            ->firstOrFail();
        $this->assertStringContainsString('Prête vers 16h', $notification->message);
        $this->assertSame("/mes-commandes/{$commande->id}", $notification->url_action);

        $this->postJson("/api/v1/vendeur/commandes/{$commande->id}/confirm-payment", ['reference_paiement' => 'OM-123'])
            ->assertOk();
        $this->assertDatabaseHas('notifications', ['user_id' => $client->id, 'titre' => "Paiement reçu : {$commande->numero_commande}"]);

        // Un paiement ne se confirme qu'une fois
        $this->postJson("/api/v1/vendeur/commandes/{$commande->id}/confirm-payment")->assertStatus(422);
    }

    public function test_le_vendeur_est_notifie_quand_le_client_annule(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $commande = $this->creerCommande($client, $vendeur);

        Sanctum::actingAs($client);
        $this->postJson("/api/v1/commandes/{$commande->id}/cancel")->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $vendeur->id,
            'titre' => "Commande {$commande->numero_commande} annulée",
        ]);
        // Le client, auteur de l'annulation, n'est pas notifié de sa propre action
        $this->assertDatabaseMissing('notifications', ['user_id' => $client->id]);
    }

    public function test_le_client_ne_peut_pas_annuler_une_commande_payee(): void
    {
        $client = $this->creerUtilisateur('client');
        $commande = $this->creerCommande($client, $this->creerUtilisateur('vendeur'), ['statut_paiement' => 'paye']);

        Sanctum::actingAs($client);
        $this->postJson("/api/v1/commandes/{$commande->id}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame('en_attente', $commande->fresh()->statut);
    }

    public function test_le_client_ne_peut_pas_annuler_pendant_un_paiement_en_ligne(): void
    {
        config(['services.notchpay.public_key' => 'pk_test_choukrane']);
        Http::fake(['api.notchpay.co/payments/trx.en_cours' => Http::response(['transaction' => ['reference' => 'trx.en_cours', 'status' => 'pending']])]);
        $client = $this->creerUtilisateur('client');
        $commande = $this->creerCommande($client, $this->creerUtilisateur('vendeur'), ['moyen_paiement' => 'mtn_momo', 'montant_total' => 2000]);
        $paiement = Paiement::create(['reference' => 'CHK-EN-COURS', 'user_id' => $client->id, 'montant' => 2000, 'moyen_paiement' => 'mtn_momo', 'notchpay_id' => 'trx.en_cours']);
        $commande->update(['paiement_id' => $paiement->id]);

        Sanctum::actingAs($client);
        $this->postJson("/api/v1/commandes/{$commande->id}/cancel")->assertStatus(422);

        $this->assertSame('en_attente', $commande->fresh()->statut);
    }

    public function test_une_commande_payee_annulee_par_le_vendeur_annonce_le_remboursement(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $commande = $this->creerCommande($client, $vendeur, ['statut_paiement' => 'paye']);

        Sanctum::actingAs($vendeur);
        $this->patchJson("/api/v1/vendeur/commandes/{$commande->id}/status", ['statut' => 'annulee'])->assertOk();

        $message = Notification::where('user_id', $client->id)->value('message');
        $this->assertStringContainsString('rembours', $message);
    }

    public function test_une_double_annulation_ne_remet_le_stock_qu_une_fois(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $produit = $this->creerProduit($vendeur, ['stock_disponible' => 5]);
        $commande = $this->creerCommande($client, $vendeur);
        LigneCommande::create([
            'commande_id' => $commande->id,
            'produit_id' => $produit->id,
            'nom_produit' => $produit->nom,
            'quantite' => 2,
            'prix_unitaire' => 1000,
            'sous_total' => 2000,
        ]);

        // Deux requêtes simultanées : chacune a lu la commande « en attente »
        $premiere = Commande::findOrFail($commande->id);
        $seconde = Commande::findOrFail($commande->id);

        $premiere->changerStatut('annulee', $client->id);
        try {
            $seconde->changerStatut('annulee', $client->id);
            $this->fail('La seconde annulation aurait dû être refusée.');
        } catch (RegleMetierException) {
            // attendu : la commande relue sous verrou est déjà annulée
        }

        $this->assertSame(7, $produit->fresh()->stock_disponible);
        $this->assertSame(1, $commande->historiques()->where('nouveau_statut', 'annulee')->count());
    }

    public function test_un_paiement_confirme_deux_fois_en_parallele_ne_l_est_qu_une_fois(): void
    {
        $client = $this->creerUtilisateur('client');
        $commande = $this->creerCommande($client, $this->creerUtilisateur('vendeur'));

        $premiere = Commande::findOrFail($commande->id);
        $seconde = Commande::findOrFail($commande->id);

        $premiere->confirmerPaiement('Espèces');
        try {
            $seconde->confirmerPaiement('Espèces');
            $this->fail('La seconde confirmation aurait dû être refusée.');
        } catch (RegleMetierException) {
            // attendu
        }

        $this->assertSame(1, Notification::where('user_id', $client->id)->count());
    }

    public function test_les_erreurs_de_l_api_ont_un_format_uniforme(): void
    {
        $this->getJson('/api/v1/auth/user')
            ->assertStatus(401)
            ->assertJsonPath('success', false);

        Sanctum::actingAs($this->creerUtilisateur('client'));

        $this->getJson('/api/v1/commandes/999999')
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Ressource introuvable.']);

        $this->postJson('/api/v1/adresses', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'errors' => ['quartier_id']]);
    }

    public function test_le_detail_admin_d_un_vendeur_affiche_ses_statistiques(): void
    {
        $vendeur = $this->creerUtilisateur('vendeur');
        $this->creerProduit($vendeur);
        $this->creerCommande($this->creerUtilisateur('client'), $vendeur, ['statut_paiement' => 'paye', 'montant_total' => 5000]);

        Sanctum::actingAs($this->creerUtilisateur('admin'));

        $this->getJson("/api/v1/admin/users/{$vendeur->id}")
            ->assertOk()
            ->assertJsonPath('data.stats.produits', 1)
            ->assertJsonPath('data.stats.commandes_recues', 1);
    }
}
