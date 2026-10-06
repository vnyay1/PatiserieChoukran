<?php

namespace App\Support;

/**
 * Numéros camerounais : saisis librement (« 690 00 09 99 », « 237690000999 »…),
 * enregistrés au format +237XXXXXXXXX.
 */
class Telephone
{
    public const REGLE = 'regex:/^\+237[0-9]{9}$/';

    public static function normaliser(mixed $telephone): mixed
    {
        // Un tableau ou un nombre est laissé tel quel : la validation le refusera
        if (! is_string($telephone)) {
            return $telephone;
        }

        $nettoye = preg_replace('/[\s.\-]+/', '', trim($telephone));

        if ($nettoye === '' || str_starts_with($nettoye, '+')) {
            return $nettoye;
        }

        return str_starts_with($nettoye, '237') ? '+'.$nettoye : '+237'.$nettoye;
    }
}
