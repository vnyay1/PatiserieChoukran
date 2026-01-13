<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsLivreur
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role !== 'livreur') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Réservé aux livreurs.',
            ], 403);
        }

        return $next($request);
    }
}
