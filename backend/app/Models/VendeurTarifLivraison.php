<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendeurTarifLivraison extends Model
{
    protected $table = 'vendeur_tarifs_livraison';

    protected $fillable = [
        'vendeur_id',
        'quartier_id',
        'tarif',
        'delai_min',
        'delai_max',
        'actif',
    ];

    protected $casts = [
        'tarif' => 'decimal:2',
        'delai_min' => 'integer',
        'delai_max' => 'integer',
        'actif' => 'boolean',
    ];

    public function vendeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }
}
