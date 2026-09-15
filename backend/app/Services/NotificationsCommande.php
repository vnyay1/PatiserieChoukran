<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Notification;
use App\Models\User;
use App\Support\Differe;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notifications liées au cycle de vie d'une commande.
 * Un échec d'envoi est journalisé mais ne bloque jamais l'action métier.
 */
class NotificationsCommande
{
    /**
     * Nouvelle commande : le vendeur est prévenu dans l'app et, s'il a un e-mail, par e-mail.
     */
    public static function nouvelleCommande(Commande $commande, User $vendeur): void
    {
        $commande->loadMissing(['user', 'ligneCommandes', 'adresseLivraison']);

        $nombreArticles = $commande->ligneCommandes->sum('quantite');
        $montant = number_format((float) $commande->montant_total, 0, ',', ' ');
        $retrait = $commande->type_livraison !== 'livraison';

        $quand = $commande->date_livraison_souhaitee
            ? ' le '.$commande->date_livraison_souhaitee->format('d/m/Y')
                .($commande->heure_livraison_souhaitee ? ' à '.substr($commande->heure_livraison_souhaitee, 0, 5) : '')
            : '';

        $modalite = $retrait
            ? 'Retrait en boutique'
            : 'Livraison à '.($commande->adresseLivraison?->quartier ?? 'l\'adresse du client').$quand;

        $message = "{$commande->user?->nom_complet} a commandé {$nombreArticles} article(s) pour {$montant} FCFA. {$modalite}.";

        self::creer($vendeur, "Nouvelle commande {$commande->numero_commande}", $message, '/admin/commandes');

        if (empty($vendeur->email)) {
            return;
        }

        self::envoyerEmail(
            $vendeur->email,
            $vendeur->nom_complet,
            "Nouvelle commande {$commande->numero_commande}",
            "{$message}\n\nRendez-vous dans votre espace vendeur pour la confirmer."
        );
    }

    /**
     * Changement de statut : le client est prévenu, sauf s'il en est l'auteur
     * (annulation par le client -> c'est le vendeur qui est prévenu).
     */
    public static function statutChange(Commande $commande, string $ancienStatut, ?int $auteurId, ?string $commentaire): void
    {
        if ($auteurId !== null && $auteurId === (int) $commande->user_id) {
            if ($commande->statut === 'annulee' && $commande->vendeur) {
                self::creer(
                    $commande->vendeur,
                    "Commande {$commande->numero_commande} annulée",
                    'Le client a annulé cette commande. Les produits ont été remis en stock.',
                    '/admin/commandes'
                );
            }

            return;
        }

        $messages = [
            'confirmee' => 'Votre commande a été confirmée'.($commande->vendeur ? ' par '.$commande->vendeur->nom_complet : '').'.',
            'en_preparation' => 'Votre commande est en cours de préparation.',
            'prete' => $commande->type_livraison === 'livraison'
                ? 'Votre commande est prête et va bientôt partir en livraison.'
                : 'Votre commande est prête : vous pouvez venir la récupérer.',
            'en_livraison' => 'Votre commande est en route vers vous.',
            'livree' => 'Votre commande a été livrée. Merci et bonne dégustation !',
            'annulee' => 'Votre commande a été annulée.',
        ];

        $message = $messages[$commande->statut] ?? 'Le statut de votre commande a changé.';
        if ($commentaire) {
            $message .= "\n".($commande->statut === 'annulee' ? 'Motif : ' : 'Note du vendeur : ').$commentaire;
        }

        self::creer(
            $commande->user,
            "Commande {$commande->numero_commande} : ".Commande::libelleStatut($commande->statut),
            $message,
            "/mes-commandes/{$commande->id}"
        );
    }

    public static function paiementConfirme(Commande $commande): void
    {
        $montant = number_format((float) $commande->montant_total, 0, ',', ' ');

        self::creer(
            $commande->user,
            "Paiement reçu : {$commande->numero_commande}",
            "Nous avons bien reçu votre paiement de {$montant} FCFA. Merci !",
            "/mes-commandes/{$commande->id}"
        );
    }

    /**
     * L'e-mail part par la file d'attente (ou juste après la réponse en queue « sync ») :
     * un SMTP lent ou injoignable ne retarde jamais la réponse du checkout.
     */
    public static function envoyerEmail(string $email, string $nom, string $sujet, string $corps): void
    {
        try {
            Differe::executer(function () use ($email, $nom, $sujet, $corps) {
                // Jamais d'exception vers le worker : un échec après remise au SMTP
                // entraînerait un nouvel essai, donc un doublon chez le destinataire
                try {
                    Mail::raw($corps, function ($mail) use ($email, $nom, $sujet) {
                        $mail->to($email, $nom)->subject($sujet);
                    });
                } catch (\Throwable $e) {
                    Log::warning('Échec de l\'envoi de l\'e-mail', ['sujet' => $sujet, 'error' => $e->getMessage()]);
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Échec de mise en file de l\'e-mail', [
                'sujet' => $sujet,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function creer(?User $destinataire, string $titre, string $message, string $url, string $type = 'commande'): void
    {
        if (! $destinataire) {
            return;
        }

        try {
            Notification::create([
                'user_id' => $destinataire->id,
                'titre' => $titre,
                'message' => $message,
                'type' => $type,
                'est_lu' => false,
                'url_action' => $url,
                'date_envoi' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Échec de création de notification', [
                'user_id' => $destinataire->id,
                'titre' => $titre,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
