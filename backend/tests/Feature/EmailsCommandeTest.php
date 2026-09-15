<?php

namespace Tests\Feature;

use App\Jobs\GenererEtEnvoyerFacture;
use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

/**
 * Nombre d'e-mails réellement remis au transport (mailer « array » en test) :
 * Mail::fake ignore Mail::raw, on compte donc les événements MessageSent.
 */
class EmailsCommandeTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    public function test_commander_puis_confirmer_envoie_exactement_deux_emails(): void
    {
        $envoyes = [];
        Event::listen(MessageSent::class, function (MessageSent $event) use (&$envoyes) {
            $envoyes[] = [
                'a' => $event->message->getTo()[0]->getAddress(),
                'sujet' => $event->message->getSubject(),
            ];
        });

        $client = $this->creerUtilisateur('client', ['email' => 'cliente@exemple.cm']);
        $vendeur = $this->creerUtilisateur('vendeur', ['email' => 'vendeur@exemple.cm']);
        $produit = $this->creerProduit($vendeur, ['prix_unitaire' => 2500]);

        Sanctum::actingAs($client);
        $this->postJson('/api/v1/panier', ['produit_id' => $produit->id, 'quantite' => 2])->assertCreated();
        $this->postJson('/api/v1/commandes', [
            'type_livraison' => 'retrait_boutique',
            'moyen_paiement' => 'especes',
        ])->assertCreated();

        $commande = Commande::firstOrFail();

        Sanctum::actingAs($vendeur);
        $this->patchJson("/api/v1/vendeur/commandes/{$commande->id}/status", ['statut' => 'confirmee'])->assertOk();
        $this->patchJson("/api/v1/vendeur/commandes/{$commande->id}/status", ['statut' => 'en_preparation'])->assertOk();

        // Un job de facturation rejoué ne renvoie pas la facture
        GenererEtEnvoyerFacture::dispatchSync($commande->id);

        $this->assertCount(2, $envoyes, json_encode($envoyes, JSON_UNESCAPED_UNICODE));
        $this->assertSame('vendeur@exemple.cm', $envoyes[0]['a']);
        $this->assertStringStartsWith('Nouvelle commande', $envoyes[0]['sujet']);
        $this->assertSame('cliente@exemple.cm', $envoyes[1]['a']);
        $this->assertStringStartsWith('Votre facture', $envoyes[1]['sujet']);
        $this->assertNotNull($commande->facture()->first()->envoyee_le);
    }
}
