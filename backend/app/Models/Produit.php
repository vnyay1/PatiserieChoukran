<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Produit extends Model
{
    // Colonnes du vendeur exposées avec un produit sur la boutique (ni téléphone ni e-mail)
    public const VENDEUR_PUBLIC = 'createur:id,nom_complet,logo_boutique,est_vendeur_vedette';

    protected $fillable = [
        'categorie_id',
        'created_by_user_id',
        'nom',
        'slug',
        'description',
        'prix_unitaire',
        'prix_promo',
        'image_principale',
        'images_secondaires',
        'stock_disponible',
        'est_disponible',
        'nombre_commandes',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'prix_promo' => 'decimal:2',
        'images_secondaires' => 'array',
        'stock_disponible' => 'integer',
        'est_disponible' => 'boolean',
        'nombre_commandes' => 'integer',
    ];

    // Relations
    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function ligneCommandes()
    {
        return $this->hasMany(LigneCommande::class);
    }

    public function paniers()
    {
        return $this->hasMany(Panier::class);
    }

    // Scopes
    public function scopeDisponible($query)
    {
        return $query->where('est_disponible', true);
    }

    /**
     * Produits proposés sur la boutique : disponibles, dans une catégorie active et,
     * s'ils appartiennent à un vendeur, seulement si ce vendeur est actif et a
     * complété son profil boutique.
     */
    public function scopeVisible($query)
    {
        return $query->disponible()
            ->whereHas('categorie', fn ($categorie) => $categorie->where('est_actif', true))
            ->where(function ($q) {
                $q->whereDoesntHave('createur', fn ($createur) => $createur->vendeurs())
                    ->orWhereHas('createur', fn ($createur) => $createur->vendeursEnActivite());
            });
    }

    // Produits qu'un client de cette ville peut se faire livrer (null : pas de filtre)
    public function scopeLivrableDans($query, ?string $ville)
    {
        if (! $ville) {
            return $query;
        }

        return $query->whereHas('createur.villesLivraison', fn ($villes) => $villes->where('ville', $ville));
    }

    // Produits des vendeurs mis en vedette par l'admin
    public function scopeVedette($query)
    {
        return $query->whereHas('createur', fn ($createur) => $createur->where('est_vendeur_vedette', true));
    }

    // Produits des vendeurs vedettes en premier (à appeler avant le tri choisi)
    public function scopeVendeursVedettesEnTete($query)
    {
        return $query->orderByDesc(
            User::select('est_vendeur_vedette')
                ->whereColumn('users.id', 'produits.created_by_user_id')
                ->limit(1)
        );
    }

    public function scopeEnStock($query)
    {
        return $query->where('stock_disponible', '>', 0);
    }

    public function scopePromotion($query)
    {
        return $query->whereNotNull('prix_promo');
    }

    // Accessors
    public function getPrixActuelAttribute()
    {
        return $this->prix_promo ?? $this->prix_unitaire;
    }

    public function getEnPromotionAttribute()
    {
        return ! is_null($this->prix_promo);
    }

    public function getPourcentageReductionAttribute()
    {
        if (! $this->en_promotion) {
            return 0;
        }

        return round((($this->prix_unitaire - $this->prix_promo) / $this->prix_unitaire) * 100);
    }

    // Méthodes utiles
    public function incrementerCommandes()
    {
        $this->increment('nombre_commandes');
    }

    public function diminuerStock($quantite)
    {
        $this->decrement('stock_disponible', $quantite);
    }

    public function augmenterStock($quantite)
    {
        $this->increment('stock_disponible', $quantite);
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($produit) {
            if (empty($produit->slug)) {
                $produit->slug = Str::slug($produit->nom);
            }
        });
    }
}
