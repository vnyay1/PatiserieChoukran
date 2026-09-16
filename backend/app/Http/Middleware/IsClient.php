<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsClient
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Réservé aux clients.',
            ], 403);
        }

        return $next($request);
    }
}
