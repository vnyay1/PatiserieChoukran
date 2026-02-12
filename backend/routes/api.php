<?php

// ===================================
// ROUTES API COMPLÈTES
// File: routes/api.php
// ===================================

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategorieController;
use App\Http\Controllers\Api\ProduitController;
use App\Http\Controllers\Api\PanierController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\AdresseController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ZoneLivraisonController;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategorieController as AdminCategorieController;
use App\Http\Controllers\Admin\ParametreSiteController as AdminParametreSiteController;
use App\Http\Controllers\Admin\ProduitController as AdminProduitController;
use App\Http\Controllers\Admin\CommandeController as AdminCommandeController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ZoneLivraisonController as AdminZoneLivraisonController;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ===================================
    // ROUTES PUBLIQUES (sans authentification)
    // ===================================
    
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Catégories
    Route::get('categories', [CategorieController::class, 'index']);
    Route::get('categories/{slug}', [CategorieController::class, 'show']);

    // Produits
    Route::prefix('produits')->group(function () {
        Route::get('/', [ProduitController::class, 'index']);
        Route::get('/featured', [ProduitController::class, 'featured']);
        Route::get('/nouveautes', [ProduitController::class, 'nouveautes']);
        Route::get('/promotions', [ProduitController::class, 'promotions']);
        Route::get('/{slug}', [ProduitController::class, 'show']);
        Route::get('/{slug}/similar', [ProduitController::class, 'similar']);
    });

    // Zones de livraison
    Route::prefix('zones-livraison')->group(function () {
        Route::get('/', [ZoneLivraisonController::class, 'index']);
        Route::get('/ville/{ville}', [ZoneLivraisonController::class, 'byCity']);
        Route::post('/search', [ZoneLivraisonController::class, 'search']);
    });

    // ===================================
    // ROUTES PROTÉGÉES (authentification requise)
    // ===================================
    
    Route::middleware('auth:sanctum')->group(function () {
        
        // Authentication
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('user', [AuthController::class, 'user']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
        });

        // Panier
        Route::prefix('panier')->group(function () {
            Route::get('/', [PanierController::class, 'index']);
            Route::get('/count', [PanierController::class, 'count']);
            Route::post('/', [PanierController::class, 'store']);
            Route::put('/{id}', [PanierController::class, 'update']);
            Route::delete('/{id}', [PanierController::class, 'destroy']);
            Route::delete('/', [PanierController::class, 'clear']);
        });

        // Commandes
        Route::prefix('commandes')->group(function () {
            Route::get('/', [CommandeController::class, 'index']);
            Route::get('/stats', [CommandeController::class, 'stats']);
            Route::post('/', [CommandeController::class, 'store']);
            Route::get('/{id}', [CommandeController::class, 'show']);
            Route::put('/{id}', [CommandeController::class, 'update']);
            Route::post('/{id}/cancel', [CommandeController::class, 'cancel']);
            Route::post('/calculate-shipping', [CommandeController::class, 'calculateShipping']);
        });

        // Adresses
        Route::prefix('adresses')->group(function () {
            Route::get('/', [AdresseController::class, 'index']);
            Route::post('/', [AdresseController::class, 'store']);
            Route::get('/{id}', [AdresseController::class, 'show']);
            Route::put('/{id}', [AdresseController::class, 'update']);
            Route::delete('/{id}', [AdresseController::class, 'destroy']);
            Route::post('/{id}/set-principal', [AdresseController::class, 'setPrincipal']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead']);
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
            Route::delete('/clear-read', [NotificationController::class, 'clearRead']);
        });
    });

    // ===================================
    // ROUTES ADMIN (authentification + rôle admin)
    // ===================================
    
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
        
        // Dashboard
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);

        // Gestion des produits
        Route::prefix('categories')->group(function () {
            Route::get('/', [AdminCategorieController::class, 'index']);
            Route::post('/', [AdminCategorieController::class, 'store']);
            Route::get('/{id}', [AdminCategorieController::class, 'show']);
            Route::put('/{id}', [AdminCategorieController::class, 'update']);
            Route::delete('/{id}', [AdminCategorieController::class, 'destroy']);
        });

        // Gestion des paramètres du site
        Route::prefix('parametres')->group(function () {
            Route::get('/', [AdminParametreSiteController::class, 'index']);
            Route::post('/', [AdminParametreSiteController::class, 'store']);
            Route::get('/{id}', [AdminParametreSiteController::class, 'show']);
            Route::put('/{id}', [AdminParametreSiteController::class, 'update']);
            Route::delete('/{id}', [AdminParametreSiteController::class, 'destroy']);
        });

        // Gestion des produits
        Route::prefix('produits')->group(function () {
            Route::get('/', [AdminProduitController::class, 'index']);
            Route::post('/', [AdminProduitController::class, 'store']);
            Route::get('/{id}', [AdminProduitController::class, 'show']);
            Route::put('/{id}', [AdminProduitController::class, 'update']);
            Route::delete('/{id}', [AdminProduitController::class, 'destroy']);
            Route::patch('/{id}/stock', [AdminProduitController::class, 'updateStock']);
        });

        // Gestion des commandes
        Route::prefix('commandes')->group(function () {
            Route::get('/', [AdminCommandeController::class, 'index']);
            Route::get('/{id}', [AdminCommandeController::class, 'show']);
            Route::patch('/{id}/status', [AdminCommandeController::class, 'updateStatus']);
            Route::post('/{id}/assign-livreur', [AdminCommandeController::class, 'assignLivreur']);
            Route::post('/{id}/confirm-payment', [AdminCommandeController::class, 'confirmPayment']);
        });

        // Gestion des utilisateurs
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index']);
            Route::get('/{id}', [AdminUserController::class, 'show']);
            Route::patch('/{id}/status', [AdminUserController::class, 'updateStatus']);
            Route::patch('/{id}/role', [AdminUserController::class, 'updateRole']);
        });

        // Gestion des zones de livraison
        Route::prefix('zones-livraison')->group(function () {
            Route::get('/', [AdminZoneLivraisonController::class, 'index']);
            Route::post('/', [AdminZoneLivraisonController::class, 'store']);
            Route::get('/{id}', [AdminZoneLivraisonController::class, 'show']);
            Route::put('/{id}', [AdminZoneLivraisonController::class, 'update']);
            Route::delete('/{id}', [AdminZoneLivraisonController::class, 'destroy']);
        });
    });

    // ===================================
    // ROUTES LIVREUR (authentification + rôle livreur)
    // ===================================
    
    Route::middleware(['auth:sanctum', 'livreur'])->prefix('livreur')->group(function () {
        
        // Mes livraisons
        Route::get('livraisons', function() {
            $livreur = request()->user();
            $livraisons = \App\Models\Commande::where('livreur_id', $livreur->id)
                ->with(['user', 'adresseLivraison', 'ligneCommandes'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $livraisons,
            ]);
        });

        // Mes livraisons en cours
        Route::get('livraisons/en-cours', function() {
            $livreur = request()->user();
            $livraisons = \App\Models\Commande::where('livreur_id', $livreur->id)
                ->whereIn('statut', ['prete', 'en_livraison'])
                ->with(['user', 'adresseLivraison', 'ligneCommandes'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $livraisons,
            ]);
        });

        // Détail d'une livraison
        Route::get('livraisons/{id}', function($id) {
            $livreur = request()->user();
            $livraison = \App\Models\Commande::where('livreur_id', $livreur->id)
                ->where('id', $id)
                ->with(['user', 'adresseLivraison', 'ligneCommandes.produit'])
                ->firstOrFail();
            
            return response()->json([
                'success' => true,
                'data' => $livraison,
            ]);
        });

        // Changer le statut de livraison
        Route::patch('livraisons/{id}/status', function($id) {
            $validated = request()->validate([
                'statut' => 'required|in:en_livraison,livree',
            ]);

            $livreur = request()->user();
            $commande = \App\Models\Commande::where('livreur_id', $livreur->id)
                ->where('id', $id)
                ->firstOrFail();
            
            $commande->changerStatut($validated['statut'], $livreur->id);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour',
                'data' => $commande,
            ]);
        });

        // Statistiques du livreur
        Route::get('stats', function() {
            $livreur = request()->user();
            
            $stats = [
                'livraisons_total' => \App\Models\Commande::where('livreur_id', $livreur->id)->count(),
                'livraisons_aujourd_hui' => \App\Models\Commande::where('livreur_id', $livreur->id)
                    ->whereDate('created_at', today())
                    ->count(),
                'en_cours' => \App\Models\Commande::where('livreur_id', $livreur->id)
                    ->whereIn('statut', ['prete', 'en_livraison'])
                    ->count(),
                'livrees_ce_mois' => \App\Models\Commande::where('livreur_id', $livreur->id)
                    ->where('statut', 'livree')
                    ->whereMonth('updated_at', now()->month)
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        });
    });
});

// ===================================
// ROUTE DE TEST (À SUPPRIMER EN PRODUCTION)
// ===================================

Route::get('test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API Pâtisserie-Glacier fonctionne !',
        'version' => '1.0',
        'timestamp' => now()->toDateTimeString(),
    ]);
});
