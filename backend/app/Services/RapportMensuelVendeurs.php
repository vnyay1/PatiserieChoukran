<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

/**
 * Rapport mensuel de l'activité de tous les vendeurs (réservé à l'admin), en PDF et CSV.
 * Périmètre : commandes créées dans le mois. Les montants excluent les commandes annulées.
 */
class RapportMensuelVendeurs
{
    public const DISQUE = 'local';

    public const FORMATS = ['pdf', 'csv'];

    // Colonnes du CSV et du PDF : clé => libellé
    private const COLONNES = [
        'nom_complet' => 'Vendeur',
        'email' => 'E-mail',
        'telephone' => 'Téléphone',
        'vedette' => 'Vedette',
        'commandes' => 'Commandes',
        'livrees' => 'Livrées',
        'annulees' => 'Annulées',
        'en_cours' => 'En cours',
        'articles_vendus' => 'Articles vendus',
        'chiffre_affaires' => 'Chiffre d\'affaires (FCFA)',
        'encaisse' => 'Encaissé (FCFA)',
        'frais_livraison' => 'Frais de livraison (FCFA)',
        'panier_moyen' => 'Panier moyen (FCFA)',
    ];

    private const COMPTEURS = ['commandes', 'livrees', 'annulees', 'en_cours', 'articles_vendus'];

    private const MONTANTS = ['chiffre_affaires', 'encaisse', 'frais_livraison'];

    /**
     * « AAAA-MM » -> premier jour du mois. Null si le format est invalide.
     */
    public static function mois(?string $mois): ?CarbonImmutable
    {
        if (! $mois || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mois)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m', $mois)->startOfMonth();
    }

    public static function libelle(CarbonImmutable $mois): string
    {
        return $mois->locale('fr')->translatedFormat('F Y');
    }

    // Un mois terminé ne change plus : son rapport peut être stocké et resservi
    public static function estClos(CarbonImmutable $mois): bool
    {
        return $mois->endOfMonth()->isPast();
    }

    public static function donnees(CarbonImmutable $mois): array
    {
        $debut = $mois->startOfMonth();
        $fin = $mois->endOfMonth();
        $enCours = Commande::STATUTS_EN_COURS;

        $parVendeur = Commande::query()
            ->whereNotNull('vendeur_id')
            ->whereBetween('created_at', [$debut, $fin])
            ->selectRaw('vendeur_id')
            ->selectRaw('COUNT(*) as commandes')
            ->selectRaw("SUM(CASE WHEN statut = 'livree' THEN 1 ELSE 0 END) as livrees")
            ->selectRaw("SUM(CASE WHEN statut = 'annulee' THEN 1 ELSE 0 END) as annulees")
            ->selectRaw('SUM(CASE WHEN statut IN ('.implode(',', array_fill(0, count($enCours), '?')).') THEN 1 ELSE 0 END) as en_cours', $enCours)
            ->selectRaw("SUM(CASE WHEN statut <> 'annulee' THEN 1 ELSE 0 END) as non_annulees")
            ->selectRaw("SUM(CASE WHEN statut <> 'annulee' THEN montant_total ELSE 0 END) as chiffre_affaires")
            ->selectRaw("SUM(CASE WHEN statut <> 'annulee' AND statut_paiement = 'paye' THEN montant_total ELSE 0 END) as encaisse")
            ->selectRaw("SUM(CASE WHEN statut <> 'annulee' THEN montant_livraison ELSE 0 END) as frais_livraison")
            ->groupBy('vendeur_id')
            ->get()
            ->keyBy('vendeur_id');

        $articles = LigneCommande::query()
            ->join('commandes', 'commandes.id', '=', 'ligne_commandes.commande_id')
            ->whereNotNull('commandes.vendeur_id')
            ->whereBetween('commandes.created_at', [$debut, $fin])
            ->where('commandes.statut', '!=', 'annulee')
            ->groupBy('commandes.vendeur_id')
            ->selectRaw('commandes.vendeur_id as vendeur_id, SUM(ligne_commandes.quantite) as quantite')
            ->pluck('quantite', 'vendeur_id');

        // Tous les vendeurs actuels, plus ceux qui ont vendu ce mois-là (même rétrogradés depuis)
        $vendeurs = User::query()
            ->where(fn ($q) => $q->vendeurs()->orWhereIn('id', $parVendeur->keys()))
            ->orderBy('nom_complet')
            ->get(['id', 'nom_complet', 'email', 'telephone', 'role', 'est_vendeur_vedette']);

        $lignes = $vendeurs->map(function (User $vendeur) use ($parVendeur, $articles) {
            $stats = $parVendeur->get($vendeur->id);
            $nonAnnulees = (int) ($stats->non_annulees ?? 0);
            $chiffreAffaires = (float) ($stats->chiffre_affaires ?? 0);

            return [
                'vendeur_id' => $vendeur->id,
                'nom_complet' => $vendeur->nom_complet,
                'email' => (string) $vendeur->email,
                'telephone' => $vendeur->telephone,
                'vedette' => $vendeur->est_vendeur_vedette ? 'Oui' : 'Non',
                'commandes' => (int) ($stats->commandes ?? 0),
                'livrees' => (int) ($stats->livrees ?? 0),
                'annulees' => (int) ($stats->annulees ?? 0),
                'en_cours' => (int) ($stats->en_cours ?? 0),
                'articles_vendus' => (int) ($articles[$vendeur->id] ?? 0),
                'chiffre_affaires' => $chiffreAffaires,
                'encaisse' => (float) ($stats->encaisse ?? 0),
                'frais_livraison' => (float) ($stats->frais_livraison ?? 0),
                'panier_moyen' => $nonAnnulees > 0 ? round($chiffreAffaires / $nonAnnulees) : 0,
            ];
        })->values();

        $totaux = ['nom_complet' => 'TOTAL', 'email' => '', 'telephone' => '', 'vedette' => ''];
        foreach ([...self::COMPTEURS, ...self::MONTANTS] as $cle) {
            $totaux[$cle] = $lignes->sum($cle);
        }
        $commandesNonAnnulees = $totaux['commandes'] - $totaux['annulees'];
        $totaux['panier_moyen'] = $commandesNonAnnulees > 0 ? round($totaux['chiffre_affaires'] / $commandesNonAnnulees) : 0;

        return [
            'mois' => $mois->format('Y-m'),
            'libelle' => self::libelle($mois),
            'clos' => self::estClos($mois),
            'genere_le' => now()->toIso8601String(),
            'colonnes' => self::COLONNES,
            'vendeurs' => $lignes->all(),
            'totaux' => $totaux,
        ];
    }

    public static function pdf(CarbonImmutable $mois): string
    {
        return Pdf::loadView('pdf.rapport-mensuel', ['rapport' => self::donnees($mois)])
            ->setPaper('a4', 'landscape')
            // Police embarquée réduite aux caractères utilisés (sinon ~850 Ko par PDF)
            ->setOption('enable_font_subsetting', true)
            ->output();
    }

    /**
     * CSV pour Excel en français : UTF-8 avec BOM, séparateur « ; », montants entiers.
     */
    public static function csv(CarbonImmutable $mois): string
    {
        $rapport = self::donnees($mois);
        $flux = fopen('php://temp', 'r+');

        fwrite($flux, "\xEF\xBB\xBF");
        fputcsv($flux, array_values(self::COLONNES), ';', '"', '');

        foreach ([...$rapport['vendeurs'], $rapport['totaux']] as $ligne) {
            fputcsv($flux, array_map(
                fn (string $cle) => is_float($ligne[$cle]) ? (int) round($ligne[$cle]) : $ligne[$cle],
                array_keys(self::COLONNES)
            ), ';', '"', '');
        }

        rewind($flux);
        $contenu = stream_get_contents($flux);
        fclose($flux);

        return $contenu;
    }

    public static function chemin(CarbonImmutable $mois, string $format): string
    {
        $cle = $mois->format('Y-m');

        return "rapports/{$cle}/rapport-vendeurs-{$cle}.{$format}";
    }

    /**
     * Génère et enregistre le PDF et le CSV du mois (remplace une version précédente).
     */
    public static function stocker(CarbonImmutable $mois): array
    {
        $chemins = [];

        foreach (self::FORMATS as $format) {
            $chemins[$format] = self::chemin($mois, $format);
            Storage::disk(self::DISQUE)->put($chemins[$format], self::contenu($mois, $format));
        }

        return $chemins;
    }

    public static function contenu(CarbonImmutable $mois, string $format): string
    {
        return $format === 'pdf' ? self::pdf($mois) : self::csv($mois);
    }

    public static function formaterMontant(float|int $montant): string
    {
        return number_format((float) $montant, 0, ',', ' ');
    }
}
