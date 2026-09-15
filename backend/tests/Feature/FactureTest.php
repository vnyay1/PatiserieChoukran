<?php

namespace Tests\Feature;

use App\Mail\FactureCommandeMail;
use App\Models\Commande;
use App\Models\Facture;
use App\Models\User;
use App\Services\Factures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class FactureTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    private User $client;

    private User $vendeur;

    private Commande $commande;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->client = $this->creerUtilisateur('client', ['email' => 'marie@exemple.cm']);
        $this->vendeur = $this->creerUtilisateur('vendeur');
        $this->commande = $this->creerCommande($this->client, $this->vendeur);
        $this->commande->ligneCommandes()->create([
            'produit_id' => $this->creerProduit($this->vendeur)->id,
            'nom_produit' => 'Fraisier',
            'quantite' => 2,
            'prix_unitaire' => 1000,
            'sous_total' => 2000,
        ]);
    }

    public function test_la_confirmation_genere_la_facture_et_l_envoie_au_client(): void
    {
        $this->confirmer();

        $facture = Facture::where('commande_id', $this->commande->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^FAC-\d{6}-0001$/', $facture->numero_facture);
        $this->assertEquals(2000, (float) $facture->montant_total);
        $this->assertNotNull($facture->envoyee_le);
        Storage::disk('local')->assertExists($facture->fichier);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($facture->fichier));

        Mail::assertSent(FactureCommandeMail::class, function (FactureCommandeMail $mail) use ($facture) {
            return $mail->hasTo('marie@exemple.cm')
                && $mail->facture->is($facture)
                && count($mail->attachments()) === 1;
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->client->id,
            'titre' => "Facture {$facture->numero_facture} disponible",
        ]);
    }

    public function test_sans_email_la_facture_est_generee_sans_envoi(): void
    {
        $this->client->update(['email' => null]);

        $this->confirmer();

        $facture = Facture::firstOrFail();
        $this->assertNull($facture->envoyee_le);
        Mail::assertNothingSent();
    }

    public function test_la_generation_est_idempotente_et_la_numerotation_suit(): void
    {
        $this->confirmer();
        $premiere = Facture::firstOrFail();

        $this->assertTrue(Factures::generer($this->commande->fresh())->is($premiere));
        $this->assertDatabaseCount('factures', 1);

        $autre = $this->creerCommande($this->client, $this->vendeur);
        $this->assertStringEndsWith('-0002', Factures::generer($autre)->numero_facture);
    }

    public function test_chaque_role_ne_telecharge_que_les_factures_de_son_perimetre(): void
    {
        $this->getJsonAs($this->client, "/api/v1/commandes/{$this->commande->id}/facture")->assertNotFound();

        $this->confirmer();
        $facture = Facture::firstOrFail();

        $this->getJsonAs($this->client, "/api/v1/commandes/{$this->commande->id}/facture")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload($facture->nomFichier());

        $this->getJsonAs($this->vendeur, "/api/v1/vendeur/commandes/{$this->commande->id}/facture")->assertOk();
        $this->getJsonAs($this->creerUtilisateur('admin'), "/api/v1/admin/commandes/{$this->commande->id}/facture")->assertOk();

        $this->getJsonAs($this->creerUtilisateur('client'), "/api/v1/commandes/{$this->commande->id}/facture")->assertNotFound();
        $this->getJsonAs($this->creerUtilisateur('vendeur'), "/api/v1/vendeur/commandes/{$this->commande->id}/facture")->assertNotFound();

        $this->getJsonAs($this->client, "/api/v1/commandes/{$this->commande->id}")
            ->assertJsonPath('data.facture.numero_facture', $facture->numero_facture)
            ->assertJsonMissingPath('data.facture.fichier');
    }

    public function test_un_pdf_disparu_est_regenere_au_telechargement(): void
    {
        $this->confirmer();
        $facture = Facture::firstOrFail();
        Storage::disk('local')->delete($facture->fichier);

        $this->getJsonAs($this->client, "/api/v1/commandes/{$this->commande->id}/facture")->assertOk();
        Storage::disk('local')->assertExists($facture->fresh()->fichier);
    }

    private function confirmer(): void
    {
        Sanctum::actingAs($this->vendeur);
        $this->patchJson("/api/v1/vendeur/commandes/{$this->commande->id}/status", ['statut' => 'confirmee'])->assertOk();
    }

    private function getJsonAs(User $user, string $url)
    {
        Sanctum::actingAs($user);

        return $this->get($url, ['Accept' => 'application/json']);
    }
}
