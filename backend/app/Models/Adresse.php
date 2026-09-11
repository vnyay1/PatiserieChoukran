<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Adresse extends Model
{
    protected $fillable = [
        'user_id',
        'libelle',
        'quartier',
        'ville',
        'zone_livraison_id',
        'quartier_id',
        'telephone_contact',
        'point_repere',
        'complement_adresse',
        'est_principale',
    ];

    protected $casts = [
        'est_principale' => 'boolean',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function zoneLivraison()
    {
        return $this->belongsTo(ZoneLivraison::class, 'zone_livraison_id');
    }

    // Nommée quartierLivraison (et non quartier) pour ne pas écraser la colonne
    // texte `quartier` dans le JSON quand la relation est chargée.
    public function quartierLivraison(): BelongsTo
    {
        return $this->belongsTo(Quartier::class, 'quartier_id');
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
