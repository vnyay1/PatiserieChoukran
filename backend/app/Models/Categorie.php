<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Categorie extends Model
{
    protected $fillable = [
        'nom',
        'slug',
        'description',
        'image',
        'ordre_affichage',
        'est_actif',
        'created_by_user_id',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'ordre_affichage' => 'integer',
    ];

    // Relations
    public function produits()
    {
        return $this->hasMany(Produit::class);
    }

    public function produitsDisponibles()
    {
        return $this->hasMany(Produit::class)->where('est_disponible', true);
    }

    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeOrdreDaffichage($query)
    {
        return $query->orderBy('ordre_affichage', 'asc');
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($categorie) {
            if (empty($categorie->slug)) {
                $categorie->slug = Str::slug($categorie->nom);
            }
        });

        static::updating(function ($categorie) {
            if ($categorie->isDirty('nom') && empty($categorie->slug)) {
                $categorie->slug = Str::slug($categorie->nom);
            }
        });
    }
}
