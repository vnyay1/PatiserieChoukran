<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;

/**
 * Factures PDF des commandes, stockées sur le disque privé « local ».
 */
class Factures
{
    public const DISQUE = 'local';

    private const TENTATIVES_NUMERO = 5;

    /**
     * Facture de la commande, créée au premier appel (appels suivants : la même facture).
     */
    public static function generer(Commande $commande): Facture
    {
        if ($facture = $commande->facture()->first()) {
            self::cheminAssure($facture);

            return $facture;
        }

        // Deux confirmations simultanées ou deux factures du même mois : l'index unique
        // tranche, on relit ou on renumérote.
        for ($tentative = 1; ; $tentative++) {
            try {
                $facture = Facture::create([
                    'commande_id' => $commande->id,
                    'numero_facture' => self::prochainNumero(),
                    'fichier' => '',
                    'montant_total' => $commande->montant_total,
                    'email_destinataire' => $commande->user?->email,
                ]);
                break;
            } catch (QueryException $e) {
                if ($existante = $commande->facture()->first()) {
                    $facture = $existante;
                    break;
                }
                if ($tentative >= self::TENTATIVES_NUMERO) {
                    throw $e;
                }
            }
        }

        $commande->setRelation('facture', $facture);
        self::ecrirePdf($facture, $commande);

        return $facture;
    }

    /**
     * Chemin absolu du PDF, régénéré s'il a disparu du disque (volume recréé, ménage…).
     */
    public static function cheminAssure(Facture $facture): string
    {
        $disque = Storage::disk(self::DISQUE);

        if (! $facture->fichier || ! $disque->exists($facture->fichier)) {
            self::ecrirePdf($facture, $facture->commande);
        }

        return $disque->path($facture->fichier);
    }

    public static function contenuPdf(Facture $facture, Commande $commande): string
    {
        $commande->loadMissing([
            'user',
            'vendeur',
            'ligneCommandes',
            'adresseLivraison.quartierLivraison',
        ]);

        return Pdf::loadView('pdf.facture', [
            'facture' => $facture,
            'commande' => $commande,
            'vendeur' => $commande->vendeur,
            'client' => $commande->user,
            'logo' => self::logoEnDataUri($commande->vendeur?->logo_boutique),
        ])->setPaper('a4')
            // Police embarquée réduite aux caractères utilisés (sinon ~850 Ko par PDF)
            ->setOption('enable_font_subsetting', true)
            ->output();
    }

    private static function ecrirePdf(Facture $facture, Commande $commande): void
    {
        $chemin = sprintf('factures/%s/%s', $facture->created_at->format('Y/m'), $facture->nomFichier());

        Storage::disk(self::DISQUE)->put($chemin, self::contenuPdf($facture, $commande));

        if ($facture->fichier !== $chemin) {
            $facture->update(['fichier' => $chemin]);
        }
    }

    /**
     * FAC-AAAAMM-NNNN : la séquence repart du plus grand numéro du mois.
     */
    private static function prochainNumero(): string
    {
        $prefixe = 'FAC-'.now()->format('Ym').'-';

        $dernier = Facture::where('numero_facture', 'like', $prefixe.'%')
            ->orderByDesc('numero_facture')
            ->value('numero_facture');

        $sequence = $dernier ? ((int) substr($dernier, strlen($prefixe))) + 1 : 1;

        return $prefixe.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Logo du vendeur intégré au PDF (dompdf ne lit pas les URL distantes).
     */
    private static function logoEnDataUri(?string $chemin): ?string
    {
        if (! $chemin || ! Storage::disk('public')->exists($chemin)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($chemin, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($chemin));
    }
}
