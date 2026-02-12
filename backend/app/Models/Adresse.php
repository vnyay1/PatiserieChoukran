<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adresse extends Model
{
    protected $fillable = [
        'user_id',
        'libelle',
        'quartier',
        'ville',
        'zone_livraison_id',
        'latitude',
        'longitude',
        'telephone_contact',
        'point_repere',
        'complement_adresse',
        'est_principale',
    ];

    protected $casts = [
        'est_principale' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class, 'adresse_livraison_id');
    }

    public function zoneLivraison()
    {
        return $this->belongsTo(ZoneLivraison::class, 'zone_livraison_id');
    }

    // Scopes
    public function scopePrincipale($query)
    {
        return $query->where('est_principale', true);
    }

    // Méthodes utiles
    public function definirCommePrincipale()
    {
        // Retirer le statut principal des autres adresses
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['est_principale' => false]);

        $this->est_principale = true;
        $this->save();
    }

    public function getAdresseCompleteAttribute()
    {
        return trim(implode(', ', array_filter([
            $this->quartier,
            $this->ville,
            $this->complement_adresse,
        ])));
    }
}
