<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Support\Telephone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

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

        // last_used_at écrit au plus une fois toutes les 5 minutes par jeton
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->definirLimiteurs();
        $this->journaliserEmails();
    }

    /**
     * Chaque e-mail remis au transport est tracé dans storage/logs/mail.log.
     */
    private function journaliserEmails(): void
    {
        Event::listen(MessageSent::class, function (MessageSent $event) {
            $message = $event->message;

            // En test, rien n'est écrit dans les journaux de développement
            Log::channel($this->app->runningUnitTests() ? 'null' : 'mail')->info('E-mail envoyé', [
                'a' => collect($message->getTo())->map(fn ($adresse) => $adresse->getAddress())->implode(', '),
                'sujet' => $message->getSubject(),
                'message_id' => $event->sent->getMessageId(),
            ]);
        });
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

        $trop = fn () => response()->json([
            'success' => false,
            'message' => 'Trop de tentatives. Veuillez patienter une minute avant de réessayer.',
        ], 429);

        // Connexion / inscription : protection contre la force brute. Le numéro est normalisé
        // comme à la connexion : « 690… », « 237690… » et « +237 690… » partagent un compteur.
        RateLimiter::for('auth', function (Request $request) use ($trop) {
            $telephone = Telephone::normaliser($request->input('telephone'));

            return [
                Limit::perMinute(10)->by($request->ip().'|'.(is_string($telephone) ? $telephone : ''))->response($trop),
                Limit::perMinute(30)->by($request->ip())->response($trop),
            ];
        });

        // Actions sensibles d'un compte connecté (mot de passe, identifiants, ouverture d'un
        // paiement NotchPay) : quelques essais par minute suffisent
        RateLimiter::for('sensible', function (Request $request) use ($trop) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip())->response($trop);
        });
    }
}
