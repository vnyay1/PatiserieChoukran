<?php

namespace App\Services;

use App\Models\User;

/**
 * Notifications liées au compte (hors cycle de vie d'une commande).
 * Comme NotificationsCommande, un échec d'envoi ne bloque jamais l'action.
 */
class NotificationsCompte
{
    /**
     * L'admin vient de faire passer l'utilisateur vendeur : il doit compléter son profil boutique.
     */
    public static function devenuVendeur(User $vendeur): void
    {
        $message = 'Votre compte vendeur est activé. Complétez votre profil boutique (e-mail, logo, description et conditions) pour commencer à vendre : vos produits restent masqués jusque-là.';

        NotificationsCommande::creer($vendeur, 'Bienvenue parmi les vendeurs Choukrane', $message, '/vendeur/profil-boutique', 'compte');

        if (filled($vendeur->email)) {
            NotificationsCommande::envoyerEmail(
                $vendeur->email,
                $vendeur->nom_complet,
                'Votre compte vendeur Choukrane est activé',
                "Bonjour {$vendeur->nom_complet},\n\n{$message}\n\nConnectez-vous à votre espace pour le compléter."
            );
        }
    }

    /**
     * Rapport mensuel des vendeurs généré par la tâche planifiée : les admins sont prévenus.
     */
    public static function rapportMensuelDisponible(string $libelleMois, string $mois): void
    {
        User::admins()->actifs()->get()->each(function (User $admin) use ($libelleMois, $mois) {
            NotificationsCommande::creer(
                $admin,
                "Rapport des vendeurs : {$libelleMois}",
                "Le rapport mensuel de {$libelleMois} est prêt (PDF et CSV).",
                "/admin/rapports?mois={$mois}",
                'systeme'
            );
        });
    }
}
