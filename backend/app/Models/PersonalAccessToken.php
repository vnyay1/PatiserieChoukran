<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as JetonSanctum;

/**
 * Sanctum écrit last_used_at à chaque requête authentifiée (un UPDATE par appel API).
 * La date n'est utile qu'à quelques minutes près : on ne l'enregistre qu'une fois par intervalle.
 */
class PersonalAccessToken extends JetonSanctum
{
    private const INTERVALLE_MISE_A_JOUR_MINUTES = 5;

    public function save(array $options = []): bool
    {
        $precedent = $this->getOriginal('last_used_at');

        if ($this->exists
            && array_keys($this->getDirty()) === ['last_used_at']
            && $precedent
            && $precedent->gt(now()->subMinutes(self::INTERVALLE_MISE_A_JOUR_MINUTES))) {
            return true;
        }

        return parent::save($options);
    }
}
