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

    public function test_les_anciennes_commandes_sont_rattachees_au_vendeur_par_migration(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $ancienne = $this->creerCommande($client, $vendeur, ['vendeur_id' => null, 'livreur_id' => $vendeur->id]);

        $migration = require database_path('migrations/2026_09_11_000001_backfill_vendeur_id_on_commandes.php');
        $migration->up();

        $this->assertSame($vendeur->id, (int) $ancienne->fresh()->vendeur_id);
    }
}
