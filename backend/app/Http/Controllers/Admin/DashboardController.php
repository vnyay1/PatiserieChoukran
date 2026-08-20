<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\User;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    /**
     * Statistiques du dashboard
     */
    public function stats(Request $request)
    {
        $validated = $request->validate([
            'periode' => 'nullable|in:aujourd_hui,semaine,mois,annee',
            'livreur_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'vendeur');
                }),
            ],
            'vendeur_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'vendeur');
                }),
            ],
        ]);

        // Période (par défaut: ce mois)
        $periode = $validated['periode'] ?? 'mois';
        $vendeurId = isset($validated['vendeur_id'])
            ? (int) $validated['vendeur_id']
            : (isset($validated['livreur_id']) ? (int) $validated['livreur_id'] : null);
        
        $dateDebut = match($periode) {
            'aujourd_hui' => Carbon::today(),
            'semaine' => Carbon::now()->startOfWeek(),
            'mois' => Carbon::now()->startOfMonth(),
            'annee' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $commandesQuery = $this->queryCommandesForDashboard($vendeurId);
        $commandesPeriodeQuery = (clone $commandesQuery)->where('created_at', '>=', $dateDebut);

        $statsClients = $this->buildClientsStats($dateDebut, $vendeurId, $commandesQuery);
        $statsProduits = $this->buildProduitsStats($vendeurId);

        // Statistiques générales
        $stats = [
            // Commandes
            'total_commandes' => (clone $commandesPeriodeQuery)->count(),
            'commandes_en_attente' => (clone $commandesQuery)->enAttente()->count(),
            'commandes_en_preparation' => (clone $commandesQuery)->enPreparation()->count(),
            'commandes_livrees' => (clone $commandesPeriodeQuery)->livree()->count(),
            
            // Revenus
            'revenus_total' => (clone $commandesPeriodeQuery)
                ->where('statut_paiement', 'paye')
                ->sum('montant_total'),
            'revenus_aujourd_hui' => (clone $commandesQuery)
                ->whereDate('created_at', Carbon::today())
                ->where('statut_paiement', 'paye')
                ->sum('montant_total'),
            
            // Clients
            'total_clients' => $statsClients['total_clients'],
            'nouveaux_clients' => $statsClients['nouveaux_clients'],
            
            // Produits
            'total_produits' => $statsProduits['total_produits'],
            'produits_stock_faible' => $statsProduits['produits_stock_faible'],
        ];

        // Graphique des ventes par jour (7 derniers jours)
        $ventesParJour = (clone $commandesQuery)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->where('statut_paiement', 'paye')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as nombre'),
                DB::raw('SUM(montant_total) as montant')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $produitsQuery = Produit::query();
        if ($vendeurId !== null) {
            $produitsQuery->where('created_by_user_id', $vendeurId);
        }

        // Top 5 produits les plus vendus
        $topProduits = $produitsQuery
            ->orderBy('nombre_commandes', 'desc')
            ->limit(5)
            ->get(['id', 'nom', 'nombre_commandes', 'image_principale']);

        // Dernières commandes
        $dernieresCommandes = (clone $commandesQuery)
            ->with(['user', 'ligneCommandes'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $vendeurs = User::vendeurs()
            ->orderBy('nom_complet')
            ->get(['id', 'nom_complet', 'email', 'telephone', 'statut']);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'ventes_par_jour' => $ventesParJour,
                'top_produits' => $topProduits,
                'dernieres_commandes' => $dernieresCommandes,
                'vendeurs' => $vendeurs,
                'livreurs' => $vendeurs,
                'selected_vendeur_id' => $vendeurId,
                'selected_livreur_id' => $vendeurId,
            ]
        ]);
    }

    private function queryCommandesForDashboard(?int $vendeurId): Builder
    {
        $query = Commande::query();

        if ($vendeurId === null) {
            return $query;
        }

        return $query
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

    private function buildClientsStats(Carbon $dateDebut, ?int $vendeurId, Builder $commandesQuery): array
    {
        if ($vendeurId === null) {
            return [
                'total_clients' => User::clients()->actifs()->count(),
                'nouveaux_clients' => User::clients()
                    ->where('created_at', '>=', $dateDebut)
                    ->count(),
            ];
        }

        return [
            'total_clients' => (clone $commandesQuery)->distinct()->count('user_id'),
            'nouveaux_clients' => (clone $commandesQuery)
                ->where('created_at', '>=', $dateDebut)
                ->distinct()
                ->count('user_id'),
        ];
    }

    private function buildProduitsStats(?int $vendeurId): array
    {
        $query = Produit::disponible();

        if ($vendeurId !== null) {
            $query->where('created_by_user_id', $vendeurId);
        }

        return [
            'total_produits' => (clone $query)->count(),
            'produits_stock_faible' => (clone $query)->whereRaw('stock_disponible <= 5')->count(),
        ];
    }
}
