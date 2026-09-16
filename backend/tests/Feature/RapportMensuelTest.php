<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class RapportMensuelTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-15 10:00'));

        $this->admin = $this->creerUtilisateur('admin');
        $client = $this->creerUtilisateur('client');
        $jean = $this->creerUtilisateur('vendeur', ['nom_complet' => 'Jean', 'est_vendeur_vedette' => true]);
        $awa = $this->creerUtilisateur('vendeur', ['nom_complet' => 'Awa']);

        // Août : Jean livre et encaisse 2 commandes, une troisième est annulée ; Awa en a une en cours
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00'));
        $this->creerCommande($client, $jean, ['statut' => 'livree', 'statut_paiement' => 'paye', 'montant_produits' => 5000, 'montant_livraison' => 1500, 'montant_total' => 6500]);
        $this->creerCommande($client, $jean, ['statut' => 'livree', 'statut_paiement' => 'paye', 'montant_produits' => 2000, 'montant_livraison' => 1500, 'montant_total' => 3500]);
        $this->creerCommande($client, $jean, ['statut' => 'annulee', 'montant_total' => 9000]);
        $this->creerCommande($client, $awa, ['statut' => 'en_preparation', 'montant_produits' => 3000, 'montant_total' => 3000]);

        // Septembre : hors du rapport d'août
        Carbon::setTestNow(Carbon::parse('2026-09-02 12:00'));
        $this->creerCommande($client, $jean, ['montant_total' => 100000]);

        Carbon::setTestNow(Carbon::parse('2026-09-15 10:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_l_apercu_agrege_l_activite_de_chaque_vendeur(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/rapports/mensuel?mois=2026-08')->assertOk();

        $response->assertJsonPath('data.libelle', 'août 2026')
            ->assertJsonPath('data.clos', true)
            ->assertJsonPath('data.vendeurs.0.nom_complet', 'Awa')
            ->assertJsonPath('data.vendeurs.0.en_cours', 1)
            ->assertJsonPath('data.vendeurs.1.nom_complet', 'Jean')
            ->assertJsonPath('data.vendeurs.1.commandes', 3)
            ->assertJsonPath('data.vendeurs.1.livrees', 2)
            ->assertJsonPath('data.vendeurs.1.annulees', 1)
            ->assertJsonPath('data.vendeurs.1.chiffre_affaires', 10000)
            ->assertJsonPath('data.vendeurs.1.encaisse', 10000)
            ->assertJsonPath('data.vendeurs.1.frais_livraison', 3000)
            ->assertJsonPath('data.vendeurs.1.panier_moyen', 5000)
            ->assertJsonPath('data.totaux.commandes', 4)
            ->assertJsonPath('data.totaux.chiffre_affaires', 13000);
    }

    public function test_le_csv_est_lisible_par_excel(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->get('/api/v1/admin/rapports/mensuel?mois=2026-08&format=csv')->assertOk();

        $response->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('rapport-vendeurs-2026-08.csv');

        $contenu = $response->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenu);

        $lignes = array_map(fn ($ligne) => str_getcsv($ligne, ';', '"', ''), array_filter(explode("\n", substr($contenu, 3))));
        $this->assertSame('Vendeur', $lignes[0][0]);
        $this->assertCount(4, $lignes); // en-tête, Awa, Jean, total
        $this->assertSame(['Jean', 'Oui', '3'], [$lignes[2][0], $lignes[2][3], $lignes[2][4]]);
        $this->assertSame('TOTAL', $lignes[3][0]);
        $this->assertSame('13000', $lignes[3][9]);

        // Mois clos : le fichier est conservé et resservi
        Storage::disk('local')->assertExists('rapports/2026-08/rapport-vendeurs-2026-08.csv');
    }

    public function test_le_pdf_est_telechargeable(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->get('/api/v1/admin/rapports/mensuel?mois=2026-08&format=pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_seul_l_admin_accede_aux_rapports(): void
    {
        Sanctum::actingAs($this->creerUtilisateur('vendeur'));
        $this->getJson('/api/v1/admin/rapports/mensuel?mois=2026-08')->assertForbidden();

        Sanctum::actingAs($this->creerUtilisateur('client'));
        $this->getJson('/api/v1/admin/rapports')->assertForbidden();

        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v1/admin/rapports/mensuel?mois=2026-13')->assertStatus(422);
        $this->getJson('/api/v1/admin/rapports/mensuel?mois=2026-10')->assertStatus(422);
        $this->getJson('/api/v1/admin/rapports')
            ->assertOk()
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('data.0.mois', '2026-09')
            ->assertJsonPath('data.0.clos', false);
    }

    public function test_la_commande_planifiee_stocke_le_mois_precedent_et_previent_les_admins(): void
    {
        $this->artisan('rapports:mensuels')->assertSuccessful();

        Storage::disk('local')->assertExists('rapports/2026-08/rapport-vendeurs-2026-08.pdf');
        Storage::disk('local')->assertExists('rapports/2026-08/rapport-vendeurs-2026-08.csv');
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => 'systeme',
            'url_action' => '/admin/rapports?mois=2026-08',
        ]);

        $this->artisan('rapports:mensuels', ['--mois' => 'août'])->assertFailed();
    }
}
