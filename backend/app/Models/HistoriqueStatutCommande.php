<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriqueStatutCommande extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'commande_id',
        'ancien_statut',
        'nouveau_statut',
        'commentaire',
        'modifie_par_user_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relations
    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function modifiePar()
    {
        return $this->belongsTo(User::class, 'modifie_par_user_id');
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($historique) {
            $historique->created_at = now();
        });
    }
}
