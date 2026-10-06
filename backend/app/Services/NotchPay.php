<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Client HTTP de l'API NotchPay (https://developer.notchpay.co) :
 * - POST /payments : crée le paiement et renvoie l'URL de la page de paiement ainsi
 *   que la référence NotchPay (transaction.reference, « trx.… ») ; la nôtre revient
 *   dans merchant_reference / trxref ;
 * - GET /payments/{référence NotchPay} : statut réel du paiement (pending, processing,
 *   complete, failed, canceled, expired). Avec notre référence, l'API répond 404 ;
 * - webhooks signés : HMAC-SHA256 du corps avec le secret du webhook (X-Notch-Signature).
 */
class NotchPay
{
    public static function estConfigure(): bool
    {
        return filled(config('services.notchpay.public_key'));
    }

    /**
     * @return array{url: string, notchpay_id: ?string}
     *
     * @throws \RuntimeException NotchPay injoignable ou paiement refusé
     */
    public static function initialiser(string $reference, float $montant, array $client, string $description): array
    {
        $reponse = self::requete()->post('/payments', array_filter([
            'amount' => (int) round($montant),
            'currency' => config('services.notchpay.currency', 'XAF'),
            'reference' => $reference,
            'description' => $description,
            'callback' => config('services.notchpay.callback_url') ?: config('app.frontend_url').'/paiement/retour',
            'email' => $client['email'] ?? null,
            'phone' => $client['telephone'] ?? null,
            'customer' => array_filter([
                'name' => $client['nom'] ?? null,
                'email' => $client['email'] ?? null,
                'phone' => $client['telephone'] ?? null,
            ]),
        ]));

        $url = $reponse->json('authorization_url');

        if ($reponse->failed() || ! $url) {
            throw new \RuntimeException($reponse->json('message') ?: 'NotchPay n\'a pas pu créer le paiement.');
        }

        return [
            'url' => $url,
            // L'API réelle ne renvoie pas transaction.id : l'identifiant utile est la référence trx.…
            'notchpay_id' => $reponse->json('transaction.reference') ?? $reponse->json('transaction.id'),
        ];
    }

    /**
     * État du paiement chez NotchPay (null si inconnu ou injoignable) : statut, montant
     * réellement payé et devise (null quand l'API ne les renvoie pas).
     *
     * @return array{statut: ?string, montant: ?int, devise: ?string}|null
     */
    public static function statut(string $reference): ?array
    {
        try {
            $reponse = self::requete()->get('/payments/'.rawurlencode($reference));
        } catch (ConnectionException) {
            return null;
        }

        if (! $reponse->successful()) {
            return null;
        }

        $montant = $reponse->json('transaction.amount');

        return [
            'statut' => $reponse->json('transaction.status'),
            'montant' => is_numeric($montant) ? (int) round((float) $montant) : null,
            'devise' => $reponse->json('transaction.currency'),
        ];
    }

    public static function signatureValide(string $corps, ?string $signature): bool
    {
        $secret = (string) config('services.notchpay.webhook_hash');

        return $secret !== '' && filled($signature)
            && hash_equals(hash_hmac('sha256', $corps, $secret), (string) $signature);
    }

    private static function requete(): PendingRequest
    {
        return Http::baseUrl(config('services.notchpay.base_url'))
            ->withHeaders(['Authorization' => (string) config('services.notchpay.public_key')])
            ->acceptJson()
            ->asJson()
            ->timeout(config('services.notchpay.timeout', 15));
    }
}
