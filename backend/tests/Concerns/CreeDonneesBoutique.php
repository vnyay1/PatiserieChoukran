<?php

namespace Tests\Concerns;

use App\Models\Adresse;
use App\Models\Categorie;
use App\Models\Commande;
use App\Models\Produit;
use App\Models\Quartier;
use App\Models\User;
use App\Models\VendeurTarifLivraison;
use Illuminate\Support\Str;

/**
 * Fabriques simples pour les tests Feature (le projet n'a pas de factories hors User).
 */
trait CreeDonneesBoutique
{
    private int $prochainTelephone = 690000100;

    protected function creerUtilisateur(string $role = 'client', array $attributs = []): User
    {
        return User::create(array_merge([
            'nom_complet' => ucfirst($role).' '.Str::random(4),
            'telephone' => '+237'.$this->prochainTelephone++,
            'mot_de_passe' => 'password123',
            'role' => $role,
            'statut' => 'actif',
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

    protected function creerTarif(User $vendeur, Quartier $quartier, float $tarif = 1000): VendeurTarifLivraison
    {
        return VendeurTarifLivraison::create([
            'vendeur_id' => $vendeur->id,
            'quartier_id' => $quartier->id,
            'tarif' => $tarif,
            'delai_min' => 30,
            'delai_max' => 60,
            'actif' => true,
        ]);
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
