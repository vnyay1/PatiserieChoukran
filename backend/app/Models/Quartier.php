<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quartier extends Model
{
    protected $fillable = [
        'nom',
        'ville',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function tarifs(): HasMany
    {
        return $this->hasMany(VendeurTarifLivraison::class);
    }

    public function adresses(): HasMany
    {
        return $this->hasMany(Adresse::class);
    }
}
