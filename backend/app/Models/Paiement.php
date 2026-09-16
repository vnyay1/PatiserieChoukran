<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Paiement mobile money (NotchPay) d'une ou plusieurs commandes.
 */
class Paiement extends Model
{
    public const STATUT_EN_ATTENTE = 'en_attente';

    public const STATUT_COMPLETE = 'complete';

    protected $fillable = [
        'reference',
        'user_id',
        'montant',
        'moyen_paiement',
        'telephone',
        'statut',
        'notchpay_id',
        'paye_le',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'paye_le' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }

    public function estTermine(): bool
    {
        return $this->statut !== self::STATUT_EN_ATTENTE;
    }
}
