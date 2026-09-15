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
            'facture:id,commande_id,numero_facture,envoyee_le',
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
            'commentaire' => 'nullable|string|max:500',
        ]);

        $commande = Commande::findOrFail($id);

        // Transitions contrôlées par Commande::changerStatut (422 si non autorisée)
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
     * Confirmer le paiement
     */
    public function confirmPayment(Request $request, $id)
    {
        $validated = $request->validate([
            'reference_paiement' => 'nullable|string',
        ]);

        $commande = Commande::findOrFail($id);

        // Refus (422) si la commande est annulée ou déjà payée ; le client est notifié
        $commande->confirmerPaiement($validated['reference_paiement'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Paiement confirmé',
            'data' => $commande,
        ]);
    }
}
