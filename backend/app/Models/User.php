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
        'logo_boutique',
        'description_boutique',
        'conditions_acceptees_le',
        'statut',
        'montant_minimum_livraison',
        'est_vendeur_vedette',
    ];

    protected $hidden = [
        'mot_de_passe',
    ];

    protected $casts = [
        'mot_de_passe' => 'hashed',
        'conditions_acceptees_le' => 'datetime',
        'montant_minimum_livraison' => 'decimal:2',
        'est_vendeur_vedette' => 'boolean',
    ];

    // Le SPA en a besoin dès la connexion pour imposer le formulaire boutique
    protected $appends = ['profil_vendeur_complet'];

    // Relations
    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    public function produits()
    {
        return $this->hasMany(Produit::class, 'created_by_user_id');
    }

    // Villes où le vendeur livre (vendeur_villes)
    public function villesLivraison()
    {
        return $this->hasMany(VendeurVille::class, 'vendeur_id');
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

    /**
     * Vendeurs dont le profil boutique est rempli : e-mail, logo, description
     * et conditions acceptées (mêmes règles que estProfilVendeurComplet()).
     */
    public function scopeProfilVendeurComplet($query)
    {
        return $query->whereNotNull('email')->where('email', '!=', '')
            ->whereNotNull('logo_boutique')->where('logo_boutique', '!=', '')
            ->whereNotNull('description_boutique')->where('description_boutique', '!=', '')
            ->whereNotNull('conditions_acceptees_le');
    }

    // Vendeurs dont les produits peuvent être vendus : actifs et profil complet
    public function scopeVendeursEnActivite($query)
    {
        return $query->vendeurs()->actifs()->profilVendeurComplet();
    }

    // Accessors & Mutators
    public function setMotDePasseAttribute($value)
    {
        $this->attributes['mot_de_passe'] = bcrypt($value);
    }

    // Toujours vrai pour un client ou un admin : seul un vendeur a un profil boutique à remplir
    public function getProfilVendeurCompletAttribute(): bool
    {
        return ! $this->isVendeur() || $this->estProfilVendeurComplet();
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

    public function estProfilVendeurComplet(): bool
    {
        return filled($this->email)
            && filled($this->logo_boutique)
            && filled($this->description_boutique)
            && $this->conditions_acceptees_le !== null;
    }

    // Un vendeur peut recevoir des commandes : actif et profil boutique complet
    public function estVendeurEnActivite(): bool
    {
        return $this->isVendeur() && $this->isActif() && $this->estProfilVendeurComplet();
    }

    /**
     * @return string[] valeurs de Quartier::VILLES
     */
    public function villesLivrees(): array
    {
        return $this->villesLivraison->pluck('ville')->sort()->values()->all();
    }

    public function livreDans(?string $ville): bool
    {
        return $ville !== null && in_array($ville, $this->villesLivrees(), true);
    }
}
