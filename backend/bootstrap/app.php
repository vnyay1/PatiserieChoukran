<?php

// ===================================
// 1. BOOTSTRAP/APP.PHP (Remplace Kernel.php dans Laravel 11)
// File: bootstrap/app.php
// ===================================

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Définir le rate limiter 'api'
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
            });

            // Connexion / inscription : protection contre la force brute
            RateLimiter::for('auth', function (Request $request) {
                $trop = fn () => response()->json([
                    'success' => false,
                    'message' => 'Trop de tentatives. Veuillez patienter une minute avant de réessayer.',
                ], 429);

                return [
                    Limit::perMinute(10)->by($request->ip().'|'.$request->input('telephone'))->response($trop),
                    Limit::perMinute(30)->by($request->ip())->response($trop),
                ];
            });
        }
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
        ]);

        // Throttle API (utilise le rate limiter 'api' défini ci-dessus)
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
