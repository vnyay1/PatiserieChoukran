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
        $query = $this->queryForVendeur($request->user()->id);

        // historique=1 : commandes terminées (annulées, ou livrées et payées)
        if ($request->boolean('historique')) {
            $query->archivee();
        } else {
            $query->visibleDansListes();
        }

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

        $query->with(['user:id,nom_complet,telephone', 'ligneCommandes', 'adresseLivraison']);

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
        // Détail accessible aussi pour les commandes archivées (historique)
        $commande = $this->queryForVendeur($request->user()->id)
            ->where('id', $id)
            ->firstOrFail()
            ->load([
                'user:id,nom_complet,telephone,email',
                'vendeur:id,nom_complet,telephone',
                'ligneCommandes.produit',
                'adresseLivraison.quartierLivraison',
                'historiques.modifiePar:id,nom_complet,role',
                'facture:id,commande_id,numero_facture,envoyee_le',
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

        // Transitions contrôlées par Commande::changerStatut (422 si non autorisée)
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

        // Refus (422) si la commande est annulée ou déjà payée ; le client est notifié
        $commande->confirmerPaiement($validated['reference_paiement'] ?? null);

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
            'a_traiter' => (clone $query)->visibleDansListes()->count(),
            'aujourd_hui' => (clone $query)->whereDate('created_at', today())->count(),
            'en_cours' => (clone $query)->whereIn('statut', Commande::STATUTS_EN_COURS)->count(),
            'livrees_ce_mois' => (clone $query)
                ->where('statut', 'livree')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
            'encaisse_ce_mois' => (clone $query)
                ->where('statut_paiement', 'paye')
                ->whereMonth('date_paiement', now()->month)
                ->whereYear('date_paiement', now()->year)
                ->sum('montant_total'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    // Une commande appartient à un seul vendeur (checkout multi-vendeur) :
    // vendeur_id suffit (renseigné aussi pour les anciennes commandes par migration)
    private function queryForVendeur(int $vendeurId)
    {
        return Commande::query()->where('vendeur_id', $vendeurId);
    }

    private function findForVendeur(int $vendeurId, int|string $commandeId): Commande
    {
        return $this->queryForVendeur($vendeurId)
            ->visibleDansListes()
            ->where('id', $commandeId)
            ->firstOrFail();
    }
}
