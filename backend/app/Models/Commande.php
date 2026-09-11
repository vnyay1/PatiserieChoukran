<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Commande extends Model
{
    // Commandes pas encore terminées (ni livrées ni annulées)
    public const STATUTS_EN_COURS = ['en_attente', 'confirmee', 'en_preparation', 'prete', 'en_livraison'];

    protected $fillable = [
        'numero_commande',
        'user_id',
        'vendeur_id',
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
        // Sérialisée en "AAAA-MM-JJ" (utilisable tel quel par <input type="date">)
        'date_livraison_souhaitee' => 'date:Y-m-d',
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

    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
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

    /**
     * Commandes considérées comme archivées pour les listes opérationnelles.
     * - annulée
     * - livrée et paiement confirmé
     */
    public function scopeArchivee($query)
    {
        return $query->where(function ($q) {
            $q->where('statut', 'annulee')
                ->orWhere(function ($sub) {
                    $sub->where('statut', 'livree')
                        ->where('statut_paiement', 'paye');
                });
        });
    }

    /**
     * Commandes visibles dans les listes opérationnelles (admin/livreur).
     */
    public function scopeVisibleDansListes($query)
    {
        return $query->where(function ($q) {
            $q->where('statut', '!=', 'annulee')
                ->where(function ($sub) {
                    $sub->where('statut', '!=', 'livree')
                        ->orWhere('statut_paiement', '!=', 'paye');
                });
        });
    }

    // Méthodes utiles

    /**
     * Numéro lisible CMD-AAAAMMJJ-NNNN. La séquence part du plus grand numéro du jour
     * (un count() produisait des doublons après une suppression ou lors de commandes
     * simultanées, rejetés par l'index unique) et l'unicité est vérifiée.
     */
    public function genererNumeroCommande(): void
    {
        $prefixe = 'CMD-'.now()->format('Ymd').'-';

        $dernier = static::where('numero_commande', 'like', $prefixe.'%')
            ->orderByDesc('numero_commande')
            ->value('numero_commande');

        $sequence = $dernier ? ((int) substr($dernier, strlen($prefixe))) + 1 : 1;

        do {
            $numero = $prefixe.str_pad((string) $sequence++, 4, '0', STR_PAD_LEFT);
        } while (static::where('numero_commande', $numero)->exists());

        $this->numero_commande = $numero;
    }

    public function changerStatut($nouveauStatut, $userId = null, $commentaire = null)
    {
        DB::transaction(function () use ($nouveauStatut, $userId, $commentaire) {
            $ancienStatut = $this->statut;
            $this->statut = $nouveauStatut;
            $this->save();

            // Annulation (client, vendeur ou admin) : les produits reviennent en stock
            if ($nouveauStatut === 'annulee' && $ancienStatut !== 'annulee') {
                $this->loadMissing('ligneCommandes.produit');
                foreach ($this->ligneCommandes as $ligne) {
                    $ligne->produit?->augmenterStock($ligne->quantite);
                }
            }

            // Enregistrer dans l'historique
            HistoriqueStatutCommande::create([
                'commande_id' => $this->id,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $nouveauStatut,
                'commentaire' => $commentaire,
                'modifie_par_user_id' => $userId,
            ]);
        });
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

    public function isArchivee(): bool
    {
        return $this->isAnnulee() || ($this->isLivree() && $this->isPaid());
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
