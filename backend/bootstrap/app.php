<?php

// ===================================
// 1. BOOTSTRAP/APP.PHP (Remplace Kernel.php dans Laravel 11)
// File: bootstrap/app.php
// ===================================

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\LostConnectionDetector;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Limiteurs « api » et « auth » : définis dans AppServiceProvider. Un callback
        // « then » ici n'est pas exécuté quand les routes sont en cache (image Docker).
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Pas de EnsureFrontendRequestsAreStateful : le SPA s'authentifie uniquement par
        // token Bearer. Avec ce middleware, les requêtes venant d'un domaine listé dans
        // SANCTUM_STATEFUL_DOMAINS (cas du Docker) exigeaient un jeton CSRF -> erreurs 419.

        // Middleware avec alias
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'client' => \App\Http\Middleware\IsClient::class,
            'vendeur' => \App\Http\Middleware\VendeurMiddleware::class,
            'actif' => \App\Http\Middleware\EnsureUserIsActive::class,
            'profil.vendeur' => \App\Http\Middleware\EnsureProfilVendeurComplet::class,
        ]);

        // Throttle API (utilise le rate limiter 'api' défini ci-dessus)
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // API : toutes les erreurs au format { success: false, message, errors? }
        $surApi = fn (Request $request) => $request->is('api/*');

        $exceptions->render(function (ValidationException $e, Request $request) use ($surApi) {
            if (! $surApi($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], $e->status);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($surApi) {
            if (! $surApi($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Vous devez être connecté pour effectuer cette action.',
            ], 401);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($surApi) {
            if (! $surApi($request)) {
                return null;
            }

            $messages = [
                403 => 'Cette action n\'est pas autorisée.',
                404 => 'Ressource introuvable.',
                405 => 'Méthode non autorisée.',
                429 => 'Trop de requêtes. Veuillez patienter quelques instants.',
            ];
            $status = $e->getStatusCode();

            return response()->json([
                'success' => false,
                'message' => $messages[$status] ?? ($status >= 500 ? 'Erreur du serveur.' : ($e->getMessage() ?: 'Requête invalide.')),
            ], $status, $e->getHeaders());
        });

        // Base de données arrêtée, figée ou injoignable : message clair, même en debug
        $exceptions->render(function (QueryException|PDOException $e, Request $request) use ($surApi) {
            $injoignable = (new LostConnectionDetector)->causedByLostConnection($e)
                || preg_match('/\[(2002|2003|2006|2013)\]/', $e->getMessage());

            if (! $surApi($request) || ! $injoignable) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Le service est momentanément indisponible (base de données injoignable). Réessayez dans quelques instants.',
            ], 503);
        });

        // Erreur inattendue : détail uniquement en mode debug (développement)
        $exceptions->render(function (Throwable $e, Request $request) use ($surApi) {
            if (! $surApi($request) || config('app.debug')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue. Veuillez réessayer plus tard.',
            ], 500);
        });
    })
    ->create();
