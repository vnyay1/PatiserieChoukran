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
        $query = Commande::with([
            'user:id,nom_complet,telephone',
            'vendeur:id,nom_complet,telephone',
            'ligneCommandes',
        ]);

        if ($request->filled('vendeur_id')) {
            $query->where('vendeur_id', $request->integer('vendeur_id'));
        }

        // Historique admin:
        // - historique=1 -> commandes archivées (annulées ou livrées+payées)
        // - défaut -> commandes opérationnelles uniquement
        if ($request->boolean('historique')) {
            $query->archivee();
        } else {
            $query->visibleDansListes();
        }

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
            $query->where(function ($q) use ($search) {
                $q->where('numero_commande', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
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
            'user:id,nom_complet,telephone,email',
            'vendeur:id,nom_complet,telephone',
            'ligneCommandes.produit',
            'adresseLivraison.quartierLivraison',
            'historiques.modifiePar:id,nom_complet,role',
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

        if ($commande->isArchivee()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande est archivée et ne peut plus être modifiée.',
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
     * Assigner un vendeur.
     */
    public function assignLivreur(Request $request, $id)
    {
        $validated = $request->validate([
            'livreur_id' => 'required|exists:users,id',
        ]);

        $commande = Commande::findOrFail($id);

        // Vérifier que c'est bien un vendeur.
        $livreur = \App\Models\User::find($validated['livreur_id']);
        if ($livreur->role !== 'vendeur') {
            return response()->json([
                'success' => false,
                'message' => 'L\'utilisateur n\'est pas un vendeur',
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
                'message' => 'Ce vendeur ne peut pas être assigné: la commande contient des produits ajoutés par un autre utilisateur.',
            ], 422);
        }

        $commande->update([
            'livreur_id' => $validated['livreur_id'],
            'vendeur_id' => $validated['livreur_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendeur assigné',
            'data' => $commande->load('vendeur:id,nom_complet,telephone'),
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

        if ($commande->isArchivee()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande est archivée et ne peut plus être modifiée.',
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
