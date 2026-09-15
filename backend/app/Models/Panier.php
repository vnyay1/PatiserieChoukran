<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Panier extends Model
{
    public const DUREE_PAR_DEFAUT_MINUTES = 1440;

    protected $fillable = [
        'user_id',
        'produit_id',
        'vendeur_id',
        'quantite',
        'prix_unitaire_actuel',
        'sous_total',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire_actuel' => 'decimal:2',
        'sous_total' => 'float',
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

    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }

    /**
     * Durée d'inactivité (minutes) après laquelle le panier est vidé : paramètre
     * « panier_duree_minutes » réglé par l'admin.
     */
    public static function dureeMinutes(): int
    {
        $minutes = (int) ParametreSite::get('panier_duree_minutes', self::DUREE_PAR_DEFAUT_MINUTES);

        return $minutes > 0 ? $minutes : self::DUREE_PAR_DEFAUT_MINUTES;
    }

    /**
     * Heure à laquelle le panier de ce client sera vidé (dernière modification + durée),
     * null si le panier est vide.
     */
    public static function expireLe(int $userId): ?Carbon
    {
        $derniereModification = static::where('user_id', $userId)->max('updated_at');

        return $derniereModification
            ? Carbon::parse($derniereModification)->addMinutes(static::dureeMinutes())
            : null;
    }

    /**
     * Vide les paniers restés sans modification plus longtemps que la durée réglée
     * (le panier entier, et non ligne par ligne).
     */
    public static function purgerExpires(?int $userId = null): int
    {
        $limite = Carbon::now()->subMinutes(static::dureeMinutes());

        $clientsExpires = static::query()
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->groupBy('user_id')
            ->havingRaw('MAX(updated_at) <= ?', [$limite])
            ->pluck('user_id');

        return $clientsExpires->isEmpty() ? 0 : static::whereIn('user_id', $clientsExpires)->delete();
    }

    // Toute modification du panier le prolonge : ajout, quantité, retrait d'un article
    public static function prolonger(int $userId): void
    {
        static::where('user_id', $userId)->update(['updated_at' => Carbon::now()]);
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
            $panier->sous_total = (float) ($panier->prix_unitaire_actuel * $panier->quantite);
        });
    }
}
