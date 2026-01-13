<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\Panier;
use App\Models\Produit;
use App\Models\ZoneLivraison;
use App\Models\HistoriqueStatutCommande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommandeController extends Controller
{
    /**
     * Liste des commandes de l'utilisateur
     */
    public function index(Request $request)
    {
        $query = Commande::where('user_id', $request->user()->id)
            ->with(['ligneCommandes.produit', 'adresseLivraison'])
            ->orderBy('created_at', 'desc');

        // Filtre par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $commandes = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $commandes,
        ]);
    }

    /**
     * Détail d'une commande
     */
    public function show(Request $request, $id)
    {
        $commande = Commande::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with([
                'ligneCommandes.produit',
                'adresseLivraison',
                'livreur',
                'historiques.modifiePar'
            ])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $commande,
        ]);
    }

    /**
     * Créer une nouvelle commande
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_livraison' => 'required|in:livraison,retrait_boutique',
            'adresse_livraison_id' => 'required_if:type_livraison,livraison|exists:adresses,id',
            'telephone_livraison' => 'nullable|string',
            'date_livraison_souhaitee' => 'required|date|after_or_equal:today',
            'heure_livraison_souhaitee' => 'required|date_format:H:i',
            'instructions_speciales' => 'nullable|string|max:500',
            'moyen_paiement' => 'required|in:orange_money,mtn_momo,especes',
            'telephone_paiement' => 'required_unless:moyen_paiement,especes|string',
        ]);

        // Vérifier que le panier n'est pas vide
        $panierItems = Panier::where('user_id', $request->user()->id)
            ->nonExpire()
            ->with('produit')
            ->get();

        if ($panierItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre panier est vide',
            ], 400);
        }

        // Calculer les montants
        $montantProduits = $panierItems->sum('sous_total');
        
        // Calculer les frais de livraison
        $montantLivraison = 0;
        if ($validated['type_livraison'] === 'livraison') {
            $adresse = $request->user()->adresses()->find($validated['adresse_livraison_id']);
            $zone = ZoneLivraison::where('ville', $adresse->ville)
                ->where('quartier', $adresse->quartier)
                ->active()
                ->first();
            
            $montantLivraison = $zone ? $zone->tarif_livraison : 1000; // Tarif par défaut
        }

        $montantTotal = $montantProduits + $montantLivraison;

        // Définir l'opérateur mobile
        $operateurMobile = null;
        if ($validated['moyen_paiement'] === 'orange_money') {
            $operateurMobile = 'orange';
        } elseif ($validated['moyen_paiement'] === 'mtn_momo') {
            $operateurMobile = 'mtn';
        }

        DB::beginTransaction();

        try {
            // Créer la commande
            $commande = Commande::create([
                'user_id' => $request->user()->id,
                'montant_produits' => $montantProduits,
                'montant_livraison' => $montantLivraison,
                'montant_total' => $montantTotal,
                'type_livraison' => $validated['type_livraison'],
                'adresse_livraison_id' => $validated['adresse_livraison_id'] ?? null,
                'telephone_livraison' => $validated['telephone_livraison'] ?? null,
                'date_livraison_souhaitee' => $validated['date_livraison_souhaitee'],
                'heure_livraison_souhaitee' => $validated['heure_livraison_souhaitee'],
                'instructions_speciales' => $validated['instructions_speciales'] ?? null,
                'moyen_paiement' => $validated['moyen_paiement'],
                'operateur_mobile' => $operateurMobile,
                'telephone_paiement' => $validated['telephone_paiement'] ?? null,
                'statut' => 'en_attente',
                'statut_paiement' => 'en_attente',
            ]);

            // Créer les lignes de commande
            foreach ($panierItems as $item) {
                LigneCommande::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $item->produit_id,
                    'nom_produit' => $item->produit->nom,
                    'quantite' => $item->quantite,
                    'prix_unitaire' => $item->prix_unitaire_actuel,
                    'sous_total' => $item->sous_total,
                ]);

                // Décrémenter le stock (indicatif)
                $item->produit->diminuerStock($item->quantite);
            }

            // Vider le panier
            Panier::where('user_id', $request->user()->id)->delete();

            // Enregistrer dans l'historique
            HistoriqueStatutCommande::create([
                'commande_id' => $commande->id,
                'ancien_statut' => null,
                'nouveau_statut' => 'en_attente',
                'commentaire' => 'Commande créée',
                'modifie_par_user_id' => $request->user()->id,
            ]);

            DB::commit();

            // Charger les relations
            $commande->load(['ligneCommandes.produit', 'adresseLivraison']);

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => $commande,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Annuler une commande
     */
    public function cancel(Request $request, $id)
    {
        $commande = Commande::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        // Vérifier si la commande peut être annulée
        if (!in_array($commande->statut, ['en_attente', 'confirmee'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut plus être annulée',
            ], 400);
        }

        $commande->changerStatut('annulee', $request->user()->id, 'Annulée par le client');

        // Remettre le stock
        foreach ($commande->ligneCommandes as $ligne) {
            $ligne->produit->augmenterStock($ligne->quantite);
        }

        return response()->json([
            'success' => true,
            'message' => 'Commande annulée',
            'data' => $commande,
        ]);
    }

    /**
     * Statistiques des commandes de l'utilisateur
     */
    public function stats(Request $request)
    {
        $userId = $request->user()->id;

        $stats = [
            'total_commandes' => Commande::where('user_id', $userId)->count(),
            'en_cours' => Commande::where('user_id', $userId)
                ->whereIn('statut', ['en_attente', 'confirmee', 'en_preparation', 'prete', 'en_livraison'])
                ->count(),
            'livrees' => Commande::where('user_id', $userId)->livree()->count(),
            'annulees' => Commande::where('user_id', $userId)->where('statut', 'annulee')->count(),
            'montant_total_depense' => Commande::where('user_id', $userId)
                ->livree()
                ->sum('montant_total'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Calculer les frais de livraison pour une adresse
     */
    public function calculateShipping(Request $request)
    {
        $validated = $request->validate([
            'adresse_id' => 'required|exists:adresses,id',
        ]);

        $adresse = $request->user()->adresses()->find($validated['adresse_id']);

        $zone = ZoneLivraison::where('ville', $adresse->ville)
            ->where('quartier', $adresse->quartier)
            ->active()
            ->first();

        $frais = $zone ? $zone->tarif_livraison : 1000;
        $delaiMin = $zone ? $zone->delai_livraison_min : 2;
        $delaiMax = $zone ? $zone->delai_livraison_max : 4;

        return response()->json([
            'success' => true,
            'data' => [
                'frais_livraison' => $frais,
                'delai_livraison_min' => $delaiMin,
                'delai_livraison_max' => $delaiMax,
                'zone' => $zone ? $zone->nom_zone : 'Zone par défaut',
            ]
        ]);
    }
}
