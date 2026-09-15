<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Support\Collection;
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

        if ($resultat['notchpay_id']) {
            $paiement->update(['notchpay_id' => $resultat['notchpay_id']]);
        }

        return ['paiement' => $paiement, 'url' => $resultat['url']];
    }

    /**
     * Relit le statut chez NotchPay et l'applique (idempotent : un paiement terminé ne
     * change plus, une commande n'est confirmée payée qu'une fois).
     */
    public static function synchroniser(Paiement $paiement): Paiement
    {
        if ($paiement->estTermine()) {
            return $paiement;
        }

        $statutNotchPay = NotchPay::statut($paiement->reference);
        $nouveauStatut = self::STATUTS[$statutNotchPay] ?? null;

        if (! $nouveauStatut) {
            return $paiement;
        }

        $commandesPayees = collect();

        DB::transaction(function () use ($paiement, $nouveauStatut, &$commandesPayees) {
            $verrouille = Paiement::whereKey($paiement->id)->lockForUpdate()->first();
            if ($verrouille->estTermine()) {
                return;
            }

            $verrouille->update([
                'statut' => $nouveauStatut,
                'paye_le' => $nouveauStatut === 'complete' ? now() : null,
            ]);

            $commandes = $verrouille->commandes()->get();
            if ($nouveauStatut === 'complete') {
                $commandesPayees = $commandes->filter(fn (Commande $commande) => ! $commande->isPaid() && ! $commande->isAnnulee());
            } else {
                Commande::whereIn('id', $commandes->where('statut_paiement', 'en_attente')->pluck('id'))
                    ->update(['statut_paiement' => 'echec']);
            }
        });

        // Après la transaction : confirmation (et notification client) commande par commande
        foreach ($commandesPayees as $commande) {
            try {
                $commande->confirmerPaiement("NotchPay {$paiement->reference}");
            } catch (\Throwable $e) {
                Log::warning('Paiement NotchPay reçu pour une commande non confirmable', [
                    'paiement' => $paiement->reference,
                    'commande' => $commande->numero_commande,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $paiement->fresh();
    }
}
