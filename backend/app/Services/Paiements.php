<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Paiements mobile money : création chez NotchPay et report du résultat sur les commandes.
 * Le statut vient toujours de l'API NotchPay (retour du client, webhook) : un simple
 * paramètre d'URL ou un corps de webhook ne suffit pas à marquer une commande payée.
 */
class Paiements
{
    public const MOYENS = ['orange_money', 'mtn_momo'];

    // Statuts NotchPay -> statut du paiement
    private const STATUTS = [
        'complete' => 'complete',
        'failed' => 'echec',
        'canceled' => 'annule',
        'expired' => 'expire',
    ];

    /**
     * Crée le paiement des commandes (même client, même moyen) et renvoie l'URL NotchPay.
     *
     * @param  Collection<int, Commande>  $commandes
     * @return array{paiement: Paiement, url: string}
     *
     * @throws \RuntimeException NotchPay non configuré ou injoignable
     */
    public static function demarrer(Collection $commandes, User $client): array
    {
        if (! NotchPay::estConfigure()) {
            throw new \RuntimeException('Le paiement en ligne n\'est pas encore configuré.');
        }

        $premiere = $commandes->first();
        $paiement = Paiement::create([
            'reference' => 'CHK-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
            'user_id' => $client->id,
            'montant' => $commandes->sum(fn (Commande $commande) => (float) $commande->montant_total),
            'moyen_paiement' => $premiere->moyen_paiement,
            'telephone' => $premiere->telephone_paiement,
        ]);

        Commande::whereIn('id', $commandes->pluck('id'))->update(['paiement_id' => $paiement->id]);
        // Nouvelle tentative après un échec : la commande redevient « à payer »
        Commande::whereIn('id', $commandes->pluck('id'))->where('statut_paiement', 'echec')->update(['statut_paiement' => 'en_attente']);

        try {
            $resultat = NotchPay::initialiser(
                $paiement->reference,
                (float) $paiement->montant,
                ['nom' => $client->nom_complet, 'email' => $client->email, 'telephone' => $paiement->telephone ?: $client->telephone],
                'Commande'.($commandes->count() > 1 ? 's ' : ' ').$commandes->pluck('numero_commande')->implode(', ')
            );
        } catch (\Throwable $e) {
            // Paiement jamais ouvert chez NotchPay : il ne reste pas « en attente »
            $paiement->update(['statut' => 'echec']);
            Log::warning('Création du paiement NotchPay impossible', ['paiement' => $paiement->reference, 'error' => $e->getMessage()]);

            throw new \RuntimeException('Le paiement en ligne est momentanément indisponible. Réessayez depuis le détail de la commande.', 0, $e);
        }

        // L'adresse est gardée : « Payer maintenant » rouvre ce paiement tant qu'il est ouvert
        $paiement->update(array_filter([
            'notchpay_id' => $resultat['notchpay_id'],
            'url_paiement' => $resultat['url'],
        ]));

        return ['paiement' => $paiement, 'url' => $resultat['url']];
    }

    /**
     * Le montant demandé à NotchPay est-il encore celui de ses commandes non annulées ?
     * Faux dès qu'une commande du même paiement a été annulée (par son vendeur ou l'admin)
     * après l'ouverture : rouvrir le lien ferait payer une commande qui ne le doit plus.
     */
    public static function couvreSesCommandes(Paiement $paiement): bool
    {
        $du = (int) round($paiement->commandes()->get()
            ->reject(fn (Commande $commande) => $commande->isAnnulee())
            ->sum(fn (Commande $commande) => (float) $commande->montant_total));

        return $du === (int) round((float) $paiement->montant);
    }

    /**
     * synchroniser() limité à un appel à NotchPay toutes les 10 secondes par paiement : les
     * actions répétées du client (modifier, annuler, payer) ne multiplient pas les appels
     * sortants. Le retour de paiement et le webhook appellent synchroniser() directement.
     */
    public static function rafraichir(Paiement $paiement): Paiement
    {
        return Cache::add("paiement:{$paiement->id}:relu", true, 10)
            ? self::synchroniser($paiement)
            : $paiement;
    }

    /**
     * Relit le statut chez NotchPay et l'applique (idempotent : un paiement terminé ne
     * change plus, une commande n'est confirmée payée qu'une fois).
     *
     * Un paiement « complete » ne confirme les commandes que si trois montants concordent :
     * celui que NotchPay a encaissé (quand l'API le renvoie), celui du paiement créé chez
     * nous, et le total actuel des commandes rattachées. Sinon l'argent est bien reçu mais
     * un admin vérifie avant toute confirmation.
     */
    public static function synchroniser(Paiement $paiement): Paiement
    {
        if ($paiement->estTermine()) {
            return $paiement;
        }

        // NotchPay ne retrouve un paiement que par sa propre référence (notchpay_id)
        $etat = NotchPay::statut($paiement->notchpay_id ?: $paiement->reference);
        $nouveauStatut = self::STATUTS[$etat['statut'] ?? null] ?? null;

        if (! $nouveauStatut) {
            return $paiement;
        }

        $commandesPayees = collect();
        $ecart = null;

        DB::transaction(function () use ($paiement, $nouveauStatut, $etat, &$commandesPayees, &$ecart) {
            $verrouille = Paiement::whereKey($paiement->id)->lockForUpdate()->first();
            if ($verrouille->estTermine()) {
                return;
            }

            $verrouille->update([
                'statut' => $nouveauStatut,
                'paye_le' => $nouveauStatut === 'complete' ? now() : null,
            ]);

            $commandes = $verrouille->commandes()->get();
            if ($nouveauStatut !== 'complete') {
                Commande::whereIn('id', $commandes->where('statut_paiement', 'en_attente')->pluck('id'))
                    ->update(['statut_paiement' => 'echec']);

                return;
            }

            if ($etat['montant'] === null) {
                // Seule la concordance interne (paiement / commandes) peut alors être vérifiée
                Log::warning('NotchPay n\'a pas renvoyé le montant encaissé', ['paiement' => $paiement->reference]);
            }

            $ecart = self::ecart($verrouille, $commandes, $etat);
            if (! $ecart) {
                $commandesPayees = $commandes->filter(fn (Commande $commande) => ! $commande->isPaid() && ! $commande->isAnnulee());
            }
        });

        if ($ecart) {
            Log::warning('Paiement NotchPay à vérifier : montants discordants', ['paiement' => $paiement->reference, ...$ecart]);
            NotificationsCompte::paiementAVerifier($paiement->reference, $ecart['recu'], $ecart['du']);
        }

        // Après la transaction : confirmation (et notification client) commande par commande
        foreach ($commandesPayees as $commande) {
            try {
                $commande->confirmerPaiement("NotchPay {$paiement->reference}");
            } catch (\Throwable $e) {
                // Argent reçu mais commande non confirmée (annulée entre-temps, verrou…) :
                // le paiement est terminé et ne sera plus relu, un admin doit trancher
                Log::warning('Paiement NotchPay reçu pour une commande non confirmable', [
                    'paiement' => $paiement->reference,
                    'commande' => $commande->numero_commande,
                    'error' => $e->getMessage(),
                ]);
                NotificationsCompte::paiementAVerifier($paiement->reference, $etat['montant'], (int) round((float) $commande->montant_total));
            }
        }

        return $paiement->fresh();
    }

    /**
     * Null si le montant encaissé correspond aux commandes, sinon le détail de l'écart.
     * Les commandes annulées depuis ne sont plus dues : leur part rend le paiement discordant.
     *
     * @param  Collection<int, Commande>  $commandes
     * @param  array{statut: ?string, montant: ?int, devise: ?string}  $etat
     * @return array{recu: ?int, attendu: int, du: int, devise: ?string}|null
     */
    private static function ecart(Paiement $paiement, Collection $commandes, array $etat): ?array
    {
        $attendu = (int) round((float) $paiement->montant);
        $du = (int) round($commandes->reject(fn (Commande $commande) => $commande->isAnnulee())
            ->sum(fn (Commande $commande) => (float) $commande->montant_total));
        $deviseAttendue = strtoupper((string) config('services.notchpay.currency', 'XAF'));

        $concordant = $du === $attendu
            && ($etat['montant'] === null || $etat['montant'] === $attendu)
            && ($etat['devise'] === null || strtoupper($etat['devise']) === $deviseAttendue);

        return $concordant ? null : [
            'recu' => $etat['montant'],
            'attendu' => $attendu,
            'du' => $du,
            'devise' => $etat['devise'],
        ];
    }
}
