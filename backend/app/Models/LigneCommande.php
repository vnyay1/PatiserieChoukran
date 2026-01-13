<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LigneCommande extends Model
{
    protected $fillable = [
        'commande_id',
        'produit_id',
        'nom_produit',
        'quantite',
        'prix_unitaire',
        'sous_total',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire' => 'decimal:2',
        'sous_total' => 'decimal:2',
    ];

    // Relations
    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ligne) {
            $ligne->sous_total = $ligne->prix_unitaire * $ligne->quantite;
        });
    }
}

