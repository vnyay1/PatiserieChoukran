<?php

namespace Tests\Concerns;

use App\Models\Adresse;
use App\Models\Categorie;
use App\Models\Commande;
use App\Models\Produit;
use App\Models\Quartier;
use App\Models\User;
use App\Models\VendeurVille;
use Illuminate\Support\Str;

/**
 * Fabriques simples pour les tests Feature (le projet n'a pas de factories hors User).
 */
trait CreeDonneesBoutique
{
    private int $prochainTelephone = 690000100;

    /**
     * Un vendeur est créé avec un profil boutique complet : sans lui, ses produits
     * seraient masqués et son espace bloqué (voir creerVendeurIncomplet()).
     */
    protected function creerUtilisateur(string $role = 'client', array $attributs = []): User
    {
        $telephone = $this->prochainTelephone++;
        $profilBoutique = $role === 'vendeur' ? [
            'email' => "vendeur{$telephone}@exemple.cm",
            'logo_boutique' => 'boutiques/logo-test.png',
            'description_boutique' => 'Pâtisserie artisanale de test, gâteaux et viennoiseries du jour.',
            'conditions_acceptees_le' => now(),
        ] : [];

        return User::create(array_merge([
            'nom_complet' => ucfirst($role).' '.Str::random(4),
            'telephone' => '+237'.$telephone,
            'mot_de_passe' => 'password123',
            'role' => $role,
            'statut' => 'actif',
        ], $profilBoutique, $attributs));
    }

    // Vendeur tout juste promu : ni e-mail, ni logo, ni description, ni conditions
    protected function creerVendeurIncomplet(array $attributs = []): User
    {
        return $this->creerUtilisateur('vendeur', array_merge([
            'email' => null,
            'logo_boutique' => null,
            'description_boutique' => null,
            'conditions_acceptees_le' => null,
        ], $attributs));
    }

    protected function creerCategorie(array $attributs = []): Categorie
    {
        $nom = $attributs['nom'] ?? 'Catégorie '.Str::random(5);

        return Categorie::create(array_merge([
            'nom' => $nom,
            'slug' => Str::slug($nom),
            'est_actif' => true,
        ], $attributs));
    }

    protected function creerProduit(?User $vendeur = null, array $attributs = []): Produit
    {
        $nom = $attributs['nom'] ?? 'Produit '.Str::random(5);

        return Produit::create(array_merge([
            'categorie_id' => $attributs['categorie_id'] ?? $this->creerCategorie()->id,
            'created_by_user_id' => $vendeur?->id,
            'nom' => $nom,
            'prix_unitaire' => 1000,
            'stock_disponible' => 10,
            'est_disponible' => true,
        ], $attributs));
    }

    protected function creerQuartier(string $nom = 'Bastos', string $ville = 'yaoundé'): Quartier
    {
        return Quartier::create(['nom' => $nom, 'ville' => $ville, 'actif' => true]);
    }

    protected function creerAdresse(User $client, Quartier $quartier): Adresse
    {
        return Adresse::create([
            'user_id' => $client->id,
            'quartier' => $quartier->nom,
            'ville' => $quartier->ville,
            'quartier_id' => $quartier->id,
            'telephone_contact' => $client->telephone,
            'est_principale' => true,
        ]);
    }

    // Le vendeur livre la ville de ce quartier (frais : tarif standard de la plateforme)
    protected function livrerVille(User $vendeur, string $ville = 'yaoundé'): VendeurVille
    {
        return VendeurVille::firstOrCreate(['vendeur_id' => $vendeur->id, 'ville' => $ville]);
    }

    protected function creerCommande(User $client, User $vendeur, array $attributs = []): Commande
    {
        return Commande::create(array_merge([
            'user_id' => $client->id,
            'vendeur_id' => $vendeur->id,
            'livreur_id' => $vendeur->id,
            'montant_produits' => 2000,
            'montant_livraison' => 0,
            'montant_total' => 2000,
            'type_livraison' => 'retrait_boutique',
            'moyen_paiement' => 'especes',
            'statut' => 'en_attente',
            'statut_paiement' => 'en_attente',
        ], $attributs));
    }
}
