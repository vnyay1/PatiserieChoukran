<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un vendeur doit avoir complété son profil boutique (e-mail, logo, description,
 * conditions acceptées) avant d'utiliser son espace. Le SPA reconnaît le code
 * « profil_vendeur_incomplet » et redirige vers le formulaire.
 */
class EnsureProfilVendeurComplet
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isVendeur() && ! $user->estProfilVendeurComplet()) {
            return response()->json([
                'success' => false,
                'code' => 'profil_vendeur_incomplet',
                'message' => 'Complétez votre profil boutique (e-mail, logo, description et conditions) pour accéder à votre espace vendeur.',
            ], 403);
        }

        return $next($request);
    }
}
