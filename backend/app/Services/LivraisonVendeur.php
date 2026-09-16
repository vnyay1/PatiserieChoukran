<?php

namespace App\Services;

use App\Models\ParametreSite;
use App\Models\Quartier;
use App\Models\User;

/**
 * Règles de livraison d'une commande vendeur, seule source de vérité du backend :
 * - les frais sont les mêmes pour tous (paramètre « frais_livraison_standard ») ;
 * - le vendeur doit livrer la ville de l'adresse (vendeur_villes) ;
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
     * Frais de livraison de ce vendeur vers cette ville.
     *
     * @throws \InvalidArgumentException ville non livrée ou minimum non atteint
     */
    public static function verifier(User $vendeur, ?string $ville, float $montantProduits): float
    {
        if (! $vendeur->livreDans($ville)) {
            throw new \InvalidArgumentException(sprintf(
                'Le vendeur « %s » ne livre pas à %s : choisissez le retrait en boutique ou retirez ses produits.',
                $vendeur->nom_complet,
                Quartier::libelleVille($ville) ?: 'cette adresse'
            ));
        }

        self::verifierMinimum($vendeur, $montantProduits);

        return self::fraisStandard();
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
