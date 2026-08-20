<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'nom_complet',
        'email',
        'telephone',
        'mot_de_passe',
        'role',
        'photo_profil',
        'adresse_principale',
        'statut',
    ];

    protected $hidden = [
        'mot_de_passe',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mot_de_passe' => 'hashed',
    ];

    // Relations
    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    public function commandesVendues()
    {
        return $this->hasMany(Commande::class, 'vendeur_id');
    }

    public function adresses()
    {
        return $this->hasMany(Adresse::class);
    }

    public function adressePrincipale()
    {
        return $this->hasOne(Adresse::class)->where('est_principale', true);
    }

    public function paniers()
    {
        return $this->hasMany(Panier::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Scopes
    public function scopeClients($query)
    {
        return $query->where('role', 'client');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeVendeurs($query)
    {
        return $query->where('role', 'vendeur');
    }

    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    // Accessors & Mutators
    public function setMotDePasseAttribute($value)
    {
        $this->attributes['mot_de_passe'] = bcrypt($value);
    }

    // Méthodes utiles
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isClient()
    {
        return $this->role === 'client';
    }

    public function isVendeur()
    {
        return $this->role === 'vendeur';
    }

    public function isActif()
    {
        return $this->statut === 'actif';
    }
}
