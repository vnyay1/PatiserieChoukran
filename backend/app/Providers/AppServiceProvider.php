<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Support\Telephone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
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

        // Derrière Caddy (production) : IP réelle du client (limitation de débit) et HTTPS
        // reconnus. Sans réglage, aucun en-tête X-Forwarded-* n'est cru.
        $proxies = config('app.proxies_de_confiance');
        if (filled($proxies)) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

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
        // Pas de seau par compte : le numéro de connexion n'est pas secret (un vendeur le donne à
        // ses clients) et un tiers bloquerait la connexion de son titulaire ; les échecs répétés sur
        // un compte sont signalés au journal (AuthController).
        RateLimiter::for('auth', function (Request $request) use ($trop) {
            $telephone = Telephone::normaliser($request->input('telephone'));
            $telephone = is_string($telephone) ? $telephone : '';
            $client = self::cleClient($request);

            return [
                Limit::perMinute(10)->by($client.'|'.$telephone)->response($trop),
                Limit::perMinute(30)->by($client)->response($trop),
            ];
        });

        // Actions sensibles d'un compte connecté (mot de passe, numéro de connexion) :
        // quelques essais par minute suffisent
        RateLimiter::for('sensible', function (Request $request) use ($trop) {
            return Limit::perMinute(5)->by($request->user()?->id ?: self::cleClient($request))->response($trop);
        });

        // Ouverture d'un paiement NotchPay (appel sortant) : compteur à part, pour qu'un
        // changement de mot de passe raté n'empêche pas de payer
        RateLimiter::for('paiement', function (Request $request) use ($trop) {
            return Limit::perMinute(5)->by('paiement|'.($request->user()?->id ?: self::cleClient($request)))->response($trop);
        });
    }

    // Adresse du client ; en IPv6, le préfixe /64 (un abonné en dispose souvent d'un entier)
    private static function cleClient(Request $request): string
    {
        $ip = (string) $request->ip();

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            ? bin2hex(substr((string) inet_pton($ip), 0, 8))
            : $ip;
    }
}
