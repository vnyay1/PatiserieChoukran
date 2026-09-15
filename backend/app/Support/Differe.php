<?php

namespace App\Support;

use function Illuminate\Support\defer;

/**
 * Travaux lents (PDF, SMTP) : par la file d'attente quand un worker tourne, sinon
 * (queue « sync », développement local) juste après l'envoi de la réponse HTTP ou
 * la fin de la commande artisan. Un rappel différé ne s'exécute qu'une fois.
 */
class Differe
{
    public static function executer(object $travail): void
    {
        if (config('queue.default') !== 'sync') {
            dispatch($travail);

            return;
        }

        // always : l'envoi part même si la réponse finale est une erreur (commande déjà enregistrée)
        defer(fn () => dispatch_sync($travail), always: true);
    }
}
