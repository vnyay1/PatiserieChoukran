<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use Illuminate\Http\Request;

class CommandeController extends Controller
{
    /**
     * Liste des commandes
     */
    public function index(Request $request)
    {
        $query = Commande::with(['user', 'ligneCommandes', 'livreur']);

        // Filtre par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtre par statut de paiement
        if ($request->has('statut_paiement')) {
            $query->where('statut_paiement', $request->statut_paiement);
        }

        // Filtre par date
        if ($request->has('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->has('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        // Recherche par numéro de commande ou client
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_commande', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('nom_complet', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%");
                  });
            });
        }

        $commandes = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $commandes,
        ]);
    }

    /**
     * Détail d'une commande
     */
    public function show($id)
    {
        $commande = Commande::with([
            'user',
            'ligneCommandes.produit',
            'adresseLivraison',
            'livreur',
            'historiques.modifiePar'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $commande,
        ]);
    }

    /**
     * Changer le statut d'une commande
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'statut' => 'required|in:en_attente,confirmee,en_preparation,prete,en_livraison,livree,annulee',
            'commentaire' => 'nullable|string',
        ]);

        $commande = Commande::findOrFail($id);

        if ($commande->statut === 'annulee') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande est annulée et ne peut plus être modifiée.',
            ], 400);
        }
        
        $commande->changerStatut(
            $validated['statut'],
            $request->user()->id,
            $validated['commentaire'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour',
            'data' => $commande->load('historiques'),
        ]);
    }

    /**
     * Assigner un livreur
     */
    public function assignLivreur(Request $request, $id)
    {
        $validated = $request->validate([
            'livreur_id' => 'required|exists:users,id',
        ]);

        $commande = Commande::findOrFail($id);
        
        // Vérifier que c'est bien un livreur
        $livreur = \App\Models\User::find($validated['livreur_id']);
        if ($livreur->role !== 'livreur') {
            return response()->json([
                'success' => false,
                'message' => 'L\'utilisateur n\'est pas un livreur',
            ], 400);
        }

        $hasForeignProducts = $commande->ligneCommandes()
            ->whereHas('produit', function ($query) use ($livreur) {
                $query->whereNull('created_by_user_id')
                    ->orWhere('created_by_user_id', '!=', $livreur->id);
            })
            ->exists();

        if ($hasForeignProducts) {
            return response()->json([
                'success' => false,
                'message' => 'Ce livreur ne peut pas être assigné: la commande contient des produits ajoutés par un autre utilisateur.',
            ], 422);
        }

        $commande->update(['livreur_id' => $validated['livreur_id']]);

        return response()->json([
            'success' => true,
            'message' => 'Livreur assigné',
            'data' => $commande->load('livreur'),
        ]);
    }

    /**
     * Confirmer le paiement
     */
    public function confirmPayment(Request $request, $id)
    {
        $validated = $request->validate([
            'reference_paiement' => 'nullable|string',
        ]);

        $commande = Commande::findOrFail($id);

        if ($commande->statut === 'annulee') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de confirmer le paiement d\'une commande annulée.',
            ], 400);
        }
        
        $commande->update([
            'statut_paiement' => 'paye',
            'date_paiement' => now(),
            'reference_paiement' => $validated['reference_paiement'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement confirmé',
            'data' => $commande,
        ]);
    }
}
