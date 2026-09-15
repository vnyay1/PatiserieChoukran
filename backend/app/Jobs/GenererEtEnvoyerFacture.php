<?php

namespace App\Jobs;

use App\Mail\FactureCommandeMail;
use App\Models\Commande;
use App\Services\Factures;
use App\Services\NotificationsCommande;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Commande confirmée : facture PDF générée, puis envoyée au client s'il a un e-mail.
 * Rejouable sans doublon (facture unique par commande, envoi mémorisé).
 */
class GenererEtEnvoyerFacture implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public int $commandeId) {}

    public function handle(): void
    {
        $commande = Commande::with(['user', 'vendeur'])->find($this->commandeId);

        if (! $commande || $commande->isAnnulee()) {
            return;
        }

        $nouvelle = ! $commande->facture()->exists();
        $facture = Factures::generer($commande);

        if ($nouvelle) {
            NotificationsCommande::creer(
                $commande->user,
                "Facture {$facture->numero_facture} disponible",
                "La facture de votre commande {$commande->numero_commande} est disponible dans le détail de la commande.",
                "/mes-commandes/{$commande->id}"
            );
        }

        $email = $commande->user?->email;
        if (! $email || $facture->envoyee_le) {
            return;
        }

        try {
            Mail::to($email, $commande->user->nom_complet)->send(new FactureCommandeMail($facture, $commande));
            $facture->update(['envoyee_le' => now(), 'email_destinataire' => $email]);
        } catch (\Throwable $e) {
            // La facture reste téléchargeable : l'échec d'envoi est seulement journalisé
            Log::warning('Échec de l\'envoi de la facture par e-mail', [
                'facture' => $facture->numero_facture,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
