<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneLivraison extends Model
{
    protected $fillable = [
        'nom_zone',
        'ville',
        'tarif_livraison',
        'delai_livraison_min',
        'delai_livraison_max',
        'est_active',
    ];

    protected $casts = [
        'tarif_livraison' => 'decimal:2',
        'delai_livraison_min' => 'integer',
        'delai_livraison_max' => 'integer',
        'est_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('est_active', true);
    }

    public function scopeVille($query, $ville)
    {
        return $query->where('ville', $ville);
    }
}
