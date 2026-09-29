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
        return min(max((int) $request->input('per_page', $defaut), 1), $max);
    }
}
