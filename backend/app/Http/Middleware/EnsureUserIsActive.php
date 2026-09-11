<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un compte suspendu ou désactivé ne doit plus pouvoir utiliser l'API,
 * même avec un token obtenu avant la suspension.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActif()) {
            $user->tokens()->delete();

            return response()->json([
                'success' => false,
                'message' => $user->statut === 'suspendu'
                    ? 'Votre compte a été suspendu. Contactez l\'administrateur.'
                    : 'Votre compte est désactivé. Contactez l\'administrateur.',
            ], 401);
        }

        return $next($request);
    }
}
