<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Produit extends Model
{
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
        'est_vedette',
        'nombre_vues',
        'nombre_commandes',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'prix_promo' => 'decimal:2',
        'images_secondaires' => 'array',
        'stock_disponible' => 'integer',
        'est_disponible' => 'boolean',
        'est_vedette' => 'boolean',
        'nombre_vues' => 'integer',
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

    // Produits proposés sur la boutique : disponibles et dans une catégorie active
    public function scopeVisible($query)
    {
        return $query->disponible()
            ->whereHas('categorie', fn ($categorie) => $categorie->where('est_actif', true));
    }

    public function scopeVedette($query)
    {
        return $query->where('est_vedette', true);
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
    public function incrementerVues()
    {
        $this->increment('nombre_vues');
    }

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
