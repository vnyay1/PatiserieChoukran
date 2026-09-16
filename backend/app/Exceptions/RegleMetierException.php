<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Action refusée par une règle métier (ex. passer une commande livrée en préparation).
 * Rendue en JSON 422 avec un message destiné à l'utilisateur.
 */
class RegleMetierException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 422);
    }
}
