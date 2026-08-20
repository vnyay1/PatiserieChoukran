<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use Illuminate\Http\Request;

class CommandeController extends Controller
{
    /**
     * Liste des commandes du vendeur (uniquement ses produits).
     */
    public function index(Request $request)
    {
        $query = $this->queryForVendeur($request->user()->id)
            ->visibleDansListes();

        // Utilisé pour le badge du menu vendeur :
        // ne compter que les commandes encore à traiter.
        if ($request->boolean('badge_only')) {
            $count = (clone $query)
                ->where('statut', '!=', 'annulee')
                ->where('statut_paiement', '!=', 'paye')
                ->count();

            return response()->json([
                'success' => true,
                'data' => ['total' => $count],
            ]);
        }

        $query->with(['user', 'ligneCommandes', 'adresseLivraison']);

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('statut_paiement')) {
            $query->where('statut_paiement', $request->statut_paiement);
        }

        if ($request->has('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

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
     * Commandes en cours du vendeur.
     */
    public function enCours(Request $request)
    {
        $commandes = $this->queryForVendeur($request->user()->id)
            ->whereIn('statut', ['confirmee', 'en_preparation', 'prete', 'en_livraison'])
            ->with(['user', 'ligneCommandes', 'adresseLivraison'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $commandes,
        ]);
    }

    /**
     * Détail d'une commande du vendeur.
     */
    public function show(Request $request, $id)
    {
        $commande = $this->findForVendeur($request->user()->id, $id)
            ->load([
                'user',
                'ligneCommandes.produit',
                'adresseLivraison',
                'historiques.modifiePar',
            ]);

        return response()->json([
            'success' => true,
            'data' => $commande,
        ]);
    }

    /**
     * Changer le statut d'une commande du vendeur.
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'statut' => 'required|in:en_attente,confirmee,en_preparation,prete,en_livraison,livree,annulee',
            'commentaire' => 'nullable|string|max:500',
        ]);

        $vendeur = $request->user();
        $commande = $this->findForVendeur($vendeur->id, $id);

        if ($commande->statut === 'annulee') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande est annulée et ne peut plus être modifiée.',
            ], 400);
        }

        $commande->changerStatut(
            $validated['statut'],
            $vendeur->id,
            $validated['commentaire'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour',
            'data' => $commande->load('historiques'),
        ]);
    }

    /**
     * Confirmer le paiement d'une commande du vendeur.
     */
    public function confirmPayment(Request $request, $id)
    {
        $validated = $request->validate([
            'reference_paiement' => 'nullable|string|max:255',
        ]);

        $commande = $this->findForVendeur($request->user()->id, $id);

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

    /**
     * Statistiques du vendeur.
     */
    public function stats(Request $request)
    {
        $query = $this->queryForVendeur($request->user()->id);

        $stats = [
            'livraisons_total' => (clone $query)->count(),
            'livraisons_aujourd_hui' => (clone $query)->whereDate('created_at', today())->count(),
            'en_cours' => (clone $query)->whereIn('statut', ['confirmee', 'en_preparation', 'prete', 'en_livraison'])->count(),
            'livrees_ce_mois' => (clone $query)
                ->where('statut', 'livree')
                ->whereMonth('updated_at', now()->month)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    private function queryForVendeur(int $vendeurId)
    {
        return Commande::query()
            ->whereHas('ligneCommandes.produit', function ($q) use ($vendeurId) {
                $q->where('created_by_user_id', $vendeurId);
            })
            ->whereDoesntHave('ligneCommandes.produit', function ($q) use ($vendeurId) {
                $q->where(function ($sub) use ($vendeurId) {
                    $sub->whereNull('created_by_user_id')
                        ->orWhere('created_by_user_id', '!=', $vendeurId);
                });
            });
    }

    private function findForVendeur(int $vendeurId, int|string $commandeId): Commande
    {
        return $this->queryForVendeur($vendeurId)
            ->visibleDansListes()
            ->where('id', $commandeId)
            ->firstOrFail();
    }
}
