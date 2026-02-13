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
use App\Http\Controllers\Livreur\CommandeController as LivreurCommandeController;

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

        Route::middleware('client')->group(function () {
            // Zones filtrées pour la commande client (par livreur du panier)
            Route::get('zones-livraison/ville/{ville}/commande', [ZoneLivraisonController::class, 'byCityForCommande']);

            // Panier (client uniquement)
            Route::prefix('panier')->group(function () {
                Route::get('/', [PanierController::class, 'index']);
                Route::get('/count', [PanierController::class, 'count']);
                Route::post('/', [PanierController::class, 'store']);
                Route::put('/{id}', [PanierController::class, 'update']);
                Route::delete('/{id}', [PanierController::class, 'destroy']);
                Route::delete('/', [PanierController::class, 'clear']);
            });

            // Commandes (client uniquement)
            Route::prefix('commandes')->group(function () {
                Route::get('/', [CommandeController::class, 'index']);
                Route::get('/stats', [CommandeController::class, 'stats']);
                Route::post('/', [CommandeController::class, 'store']);
                Route::get('/{id}', [CommandeController::class, 'show']);
                Route::put('/{id}', [CommandeController::class, 'update']);
                Route::post('/{id}/cancel', [CommandeController::class, 'cancel']);
                Route::post('/calculate-shipping', [CommandeController::class, 'calculateShipping']);
            });
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

        // Gestion des commandes du livreur (uniquement ses propres produits)
        Route::prefix('commandes')->group(function () {
            Route::get('/', [LivreurCommandeController::class, 'index']);
            Route::get('/en-cours', [LivreurCommandeController::class, 'enCours']);
            Route::get('/stats', [LivreurCommandeController::class, 'stats']);
            Route::get('/{id}', [LivreurCommandeController::class, 'show']);
            Route::patch('/{id}/status', [LivreurCommandeController::class, 'updateStatus']);
            Route::post('/{id}/confirm-payment', [LivreurCommandeController::class, 'confirmPayment']);
        });

        // Alias historiques "livraisons" (compatibilité)
        Route::get('livraisons', [LivreurCommandeController::class, 'index']);
        Route::get('livraisons/en-cours', [LivreurCommandeController::class, 'enCours']);
        Route::get('livraisons/{id}', [LivreurCommandeController::class, 'show']);
        Route::patch('livraisons/{id}/status', [LivreurCommandeController::class, 'updateStatus']);
        Route::post('livraisons/{id}/confirm-payment', [LivreurCommandeController::class, 'confirmPayment']);

        // Statistiques du livreur
        Route::get('stats', [LivreurCommandeController::class, 'stats']);

        // Ajouts catalogue autorisés pour le livreur
        Route::prefix('catalogue')->group(function () {
            Route::get('categories', [AdminCategorieController::class, 'index']);
            Route::get('categories/{id}', [AdminCategorieController::class, 'show']);
            Route::post('categories', [AdminCategorieController::class, 'store']);
            Route::put('categories/{id}', [AdminCategorieController::class, 'update']);
            Route::delete('categories/{id}', [AdminCategorieController::class, 'destroy']);

            Route::get('produits', [AdminProduitController::class, 'index']);
            Route::get('produits/{id}', [AdminProduitController::class, 'show']);
            Route::post('produits', [AdminProduitController::class, 'store']);
            Route::put('produits/{id}', [AdminProduitController::class, 'update']);
            Route::delete('produits/{id}', [AdminProduitController::class, 'destroy']);

            Route::get('zones-livraison', [AdminZoneLivraisonController::class, 'index']);
            Route::get('zones-livraison/{id}', [AdminZoneLivraisonController::class, 'show']);
            Route::post('zones-livraison', [AdminZoneLivraisonController::class, 'store']);
            Route::put('zones-livraison/{id}', [AdminZoneLivraisonController::class, 'update']);
            Route::delete('zones-livraison/{id}', [AdminZoneLivraisonController::class, 'destroy']);
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
