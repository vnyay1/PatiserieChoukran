<?php

namespace Tests\Feature;

use App\Models\Panier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

/**
 * Un produit n'occupe qu'une ligne du panier d'un client : sinon le contrôle de stock
 * (fait ligne par ligne au checkout) laissait vendre plus que le stock disponible.
 */
class PanierUniciteTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    public function test_une_seule_ligne_par_produit_dans_le_panier_d_un_client(): void
    {
        $client = $this->creerUtilisateur('client');
        $produit = $this->creerProduit($this->creerUtilisateur('vendeur'));
        $ligne = ['user_id' => $client->id, 'produit_id' => $produit->id, 'quantite' => 1, 'prix_unitaire_actuel' => 1000];

        Panier::create($ligne);

        $this->expectException(UniqueConstraintViolationException::class);
        Panier::create($ligne);
    }

    public function test_deux_ajouts_simultanes_du_meme_produit_se_cumulent(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $produit = $this->creerProduit($vendeur, ['stock_disponible' => 10]);

        // Une autre requête (double clic) insère la ligne juste avant celle-ci
        $concurrenteInseree = false;
        Panier::creating(function () use (&$concurrenteInseree, $client, $produit, $vendeur) {
            if (! $concurrenteInseree) {
                $concurrenteInseree = true;
                DB::table('paniers')->insert([
                    'user_id' => $client->id,
                    'produit_id' => $produit->id,
                    'vendeur_id' => $vendeur->id,
                    'quantite' => 1,
                    'prix_unitaire_actuel' => 1000,
                    'sous_total' => 1000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        Sanctum::actingAs($client);
        $this->postJson('/api/v1/panier', ['produit_id' => $produit->id, 'quantite' => 2])->assertCreated();

        $this->assertSame(1, Panier::where('user_id', $client->id)->count());
        $this->assertSame(3, Panier::where('user_id', $client->id)->value('quantite'));
    }
}
