<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\ParametreSite;

class Panier extends Model
{
    protected $fillable = [
        'user_id',
        'produit_id',
        'quantite',
        'prix_unitaire_actuel',
        'sous_total',
        'date_expiration',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire_actuel' => 'decimal:2',
        'sous_total' => 'float',
        'date_expiration' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    // Scopes
    public function scopeNonExpire($query)
    {
        return $query->where('date_expiration', '>', now());
    }

    public function scopeExpire($query)
    {
        return $query->where('date_expiration', '<=', now());
    }

    // Méthodes utiles
    public function isExpire()
    {
        return $this->date_expiration <= now();
    }

    public static function getExpirationHeures(): int
    {
        $heures = (int) ParametreSite::get('panier_expiration_heures', 24);

        return $heures > 0 ? $heures : 24;
    }

    public static function prochaineExpiration(): Carbon
    {
        return Carbon::now()->addHours(static::getExpirationHeures());
    }

    public static function purgerExpires(?int $userId = null): int
    {
        $query = static::expire();

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->delete();
    }

    public function calculerSousTotal()
    {
        $this->sous_total = ($this->prix_unitaire_actuel * $this->quantite);
        $this->save();
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($panier) {
            // Expire selon le paramètre du site (24h par défaut)
            if (empty($panier->date_expiration)) {
                $panier->date_expiration = static::prochaineExpiration();
            }
            $panier->sous_total = (float)($panier->prix_unitaire_actuel * $panier->quantite);
        });
    }
}
