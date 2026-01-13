<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\User;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Statistiques du dashboard
     */
    public function stats(Request $request)
    {
        // Période (par défaut: ce mois)
        $periode = $request->get('periode', 'mois');
        
        $dateDebut = match($periode) {
            'aujourd_hui' => Carbon::today(),
            'semaine' => Carbon::now()->startOfWeek(),
            'mois' => Carbon::now()->startOfMonth(),
            'annee' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        // Statistiques générales
        $stats = [
            // Commandes
            'total_commandes' => Commande::where('created_at', '>=', $dateDebut)->count(),
            'commandes_en_attente' => Commande::enAttente()->count(),
            'commandes_en_preparation' => Commande::enPreparation()->count(),
            'commandes_livrees' => Commande::where('created_at', '>=', $dateDebut)->livree()->count(),
            
            // Revenus
            'revenus_total' => Commande::where('created_at', '>=', $dateDebut)
                ->where('statut_paiement', 'paye')
                ->sum('montant_total'),
            'revenus_aujourd_hui' => Commande::whereDate('created_at', Carbon::today())
                ->where('statut_paiement', 'paye')
                ->sum('montant_total'),
            
            // Clients
            'total_clients' => User::clients()->actifs()->count(),
            'nouveaux_clients' => User::clients()
                ->where('created_at', '>=', $dateDebut)
                ->count(),
            
            // Produits
            'total_produits' => Produit::disponible()->count(),
            'produits_stock_faible' => Produit::disponible()
                ->whereRaw('stock_disponible <= 5')
                ->count(),
        ];

        // Graphique des ventes par jour (7 derniers jours)
        $ventesParJour = Commande::where('created_at', '>=', Carbon::now()->subDays(7))
            ->where('statut_paiement', 'paye')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as nombre'),
                DB::raw('SUM(montant_total) as montant')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top 5 produits les plus vendus
        $topProduits = Produit::orderBy('nombre_commandes', 'desc')
            ->limit(5)
            ->get(['id', 'nom', 'nombre_commandes', 'image_principale']);

        // Dernières commandes
        $dernieresCommandes = Commande::with(['user', 'ligneCommandes'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'ventes_par_jour' => $ventesParJour,
                'top_produits' => $topProduits,
                'dernieres_commandes' => $dernieresCommandes,
            ]
        ]);
    }
}
