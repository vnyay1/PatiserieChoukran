<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Taille de page demandée (?per_page), bornée : une page géante (per_page=1000000)
     * chargerait toute la table en mémoire.
     */
    protected function parPage(Request $request, int $defaut = 15, int $max = 100): int
    {
        // Vide ou non numérique (?per_page=, ?per_page=abc) : taille par défaut
        $demande = $request->input('per_page');
        $taille = is_numeric($demande) ? (int) $demande : $defaut;

        return min(max($taille, 1), $max);
    }
}
