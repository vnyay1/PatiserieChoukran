<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    protected $fillable = [
        'numero_commande',
        'user_id',
        'montant_produits',
        'montant_livraison',
        'montant_total',
        'devise',
        'statut',
        'statut_paiement',
        'type_livraison',
        'adresse_livraison_id',
        'telephone_livraison',
        'date_livraison_souhaitee',
        'heure_livraison_souhaitee',
        'instructions_speciales',
        'moyen_paiement',
        'operateur_mobile',
        'telephone_paiement',
        'reference_paiement',
        'date_paiement',
        'livreur_id',
    ];

    protected $casts = [
        'montant_produits' => 'decimal:2',
        'montant_livraison' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'date_livraison_souhaitee' => 'date',
        'date_paiement' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function livreur()
    {
        return $this->belongsTo(User::class, 'livreur_id');
    }

    public function adresseLivraison()
    {
        return $this->belongsTo(Adresse::class, 'adresse_livraison_id');
    }

    public function ligneCommandes()
    {
        return $this->hasMany(LigneCommande::class);
    }

    public function historiques()
    {
        return $this->hasMany(HistoriqueStatutCommande::class);
    }

    // Scopes
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeConfirmee($query)
    {
        return $query->where('statut', 'confirmee');
    }

    public function scopeEnPreparation($query)
    {
        return $query->where('statut', 'en_preparation');
    }

    public function scopeLivree($query)
    {
        return $query->where('statut', 'livree');
    }

    public function scopePayee($query)
    {
        return $query->where('statut_paiement', 'paye');
    }

    public function scopeEnAttentePaiement($query)
    {
        return $query->where('statut_paiement', 'en_attente');
    }

    // Méthodes utiles
    public function genererNumeroCommande()
    {
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;
        $this->numero_commande = 'CMD-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function changerStatut($nouveauStatut, $userId = null, $commentaire = null)
    {
        $ancienStatut = $this->statut;
        $this->statut = $nouveauStatut;
        $this->save();

        // Enregistrer dans l'historique
        HistoriqueStatutCommande::create([
            'commande_id' => $this->id,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $nouveauStatut,
            'commentaire' => $commentaire,
            'modifie_par_user_id' => $userId,
        ]);
    }

    public function isPaid()
    {
        return $this->statut_paiement === 'paye';
    }

    public function isLivree()
    {
        return $this->statut === 'livree';
    }

    public function isAnnulee()
    {
        return $this->statut === 'annulee';
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($commande) {
            if (empty($commande->numero_commande)) {
                $commande->genererNumeroCommande();
            }
        });
    }
}
