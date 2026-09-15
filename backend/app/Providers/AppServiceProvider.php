<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Un serveur MySQL figé (qui accepte la connexion sans répondre) bloquait une
        // requête HTTP pendant mysqlnd.net_read_timeout, 24 h par défaut, et avec elle
        // tout `php artisan serve`. En HTTP, chaque lecture est plafonnée ; la console
        // (migrations, worker, rapports planifiés) garde le réglage de PHP.
        $delai = (int) config('database.read_timeout');
        if ($delai > 0 && ! $this->app->runningInConsole()) {
            ini_set('mysqlnd.net_read_timeout', (string) $delai);
        }

        $this->definirLimiteurs();
    }

    /**
     * Limiteurs de débit. Ici plutôt que dans bootstrap/app.php (withRouting then:) :
     * ce callback est ignoré quand les routes sont en cache, et toute requête API
     * échouait alors avec « Rate limiter [api] is not defined » (image Docker).
     */
    private function definirLimiteurs(): void
    {
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
}
