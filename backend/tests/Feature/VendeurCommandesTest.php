<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class VendeurCommandesTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    public function test_le_vendeur_ne_voit_que_ses_commandes_et_dispose_d_un_historique(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $autreVendeur = $this->creerUtilisateur('vendeur');

        $enCours = $this->creerCommande($client, $vendeur, ['statut' => 'confirmee']);
        $archivee = $this->creerCommande($client, $vendeur, ['statut' => 'livree', 'statut_paiement' => 'paye']);
        $this->creerCommande($client, $autreVendeur);

        Sanctum::actingAs($vendeur);

        $this->getJson('/api/v1/vendeur/commandes')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $enCours->id);

        $this->getJson('/api/v1/vendeur/commandes?historique=1')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $archivee->id);

        // Le détail d'une commande archivée reste consultable, avec les infos de livraison
        $this->getJson("/api/v1/vendeur/commandes/{$archivee->id}")
            ->assertOk()
            ->assertJsonPath('data.user.telephone', $client->telephone);
    }
}
