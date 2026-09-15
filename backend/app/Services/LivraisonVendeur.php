<?php

namespace App\Services;

use App\Models\ParametreSite;
use App\Models\User;
use App\Models\VendeurTarifLivraison;

/**
 * Règles de livraison d'une commande vendeur, seule source de vérité du backend :
 * - les frais sont les mêmes pour tous (paramètre « frais_livraison_standard ») ;
 * - le vendeur doit desservir le quartier (vendeur_tarifs_livraison, actif) ;
 * - le montant des produits doit atteindre le minimum fixé par le vendeur.
 * Le retrait en boutique n'est soumis à aucune de ces règles.
 */
class LivraisonVendeur
{
    public const FRAIS_PAR_DEFAUT = 1500;

    public static function fraisStandard(): float
    {
        return (float) ParametreSite::get('frais_livraison_standard', self::FRAIS_PAR_DEFAUT);
    }

    /**
     * Frais et délais de livraison de ce vendeur vers ce quartier.
     *
     * @return array{frais: float, delai_min: int, delai_max: int, quartier: array}
     *
     * @throws \InvalidArgumentException quartier non desservi ou minimum non atteint
     */
    public static function verifier(User $vendeur, int $quartierId, float $montantProduits): array
    {
        $desserte = VendeurTarifLivraison::where('vendeur_id', $vendeur->id)
            ->where('quartier_id', $quartierId)
            ->where('actif', true)
            ->with('quartier')
            ->first();

        if (! $desserte || ($desserte->quartier && ! $desserte->quartier->actif)) {
            throw new \InvalidArgumentException("Le vendeur « {$vendeur->nom_complet} » ne livre pas dans votre quartier.");
        }

        self::verifierMinimum($vendeur, $montantProduits);

        return [
            'frais' => self::fraisStandard(),
            'delai_min' => (int) $desserte->delai_min,
            'delai_max' => (int) $desserte->delai_max,
            'quartier' => [
                'id' => $desserte->quartier?->id,
                'nom' => $desserte->quartier?->nom,
                'ville' => $desserte->quartier?->ville,
                'delai_min' => (int) $desserte->delai_min,
                'delai_max' => (int) $desserte->delai_max,
            ],
        ];
    }

    /**
     * @throws \InvalidArgumentException montant des produits sous le minimum du vendeur
     */
    public static function verifierMinimum(User $vendeur, float $montantProduits): void
    {
        $minimum = (float) $vendeur->montant_minimum_livraison;

        if ($minimum > 0 && $montantProduits < $minimum) {
            throw new \InvalidArgumentException(sprintf(
                'Livraison possible chez « %s » à partir de %s FCFA d\'achat (il manque %s FCFA) : ajoutez des produits ou choisissez le retrait en boutique.',
                $vendeur->nom_complet,
                self::formaterMontant($minimum),
                self::formaterMontant($minimum - $montantProduits)
            ));
        }
    }

    private static function formaterMontant(float $montant): string
    {
        return number_format(ceil($montant), 0, ',', ' ');
    }
}
