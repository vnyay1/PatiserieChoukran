<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ville dans laquelle un vendeur livre ses commandes.
 */
class VendeurVille extends Model
{
    protected $table = 'vendeur_villes';

    protected $fillable = [
        'vendeur_id',
        'ville',
    ];

    public function vendeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }
}
