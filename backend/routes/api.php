<?php

// ===================================
// ROUTES API COMPLÈTES
// File: routes/api.php
// ===================================

use App\Http\Controllers\Admin\CategorieController as AdminCategorieController;
// Controllers
use App\Http\Controllers\Admin\CommandeController as AdminCommandeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ParametreSiteController as AdminParametreSiteController;
use App\Http\Controllers\Admin\ProduitController as AdminProduitController;
use App\Http\Controllers\Admin\QuartierController as AdminQuartierController;
use App\Http\Controllers\Admin\RapportController as AdminRapportController;
use App\Http\Controllers\Admin\ReglagesController as AdminReglagesController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AccueilController;
use App\Http\Controllers\Api\AdresseController;
// Admin Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategorieController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\LivraisonController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\PanierController;
use App\Http\Controllers\Api\ProduitController;
use App\Http\Controllers\Api\VendeurController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\Vendeur\CommandeController as VendeurCommandeController;
use App\Http\Controllers\Vendeur\LivraisonController as VendeurLivraisonController;
use App\Http\Controllers\Vendeur\ProfilBoutiqueController as VendeurProfilBoutiqueController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ===================================
    // ROUTES PUBLIQUES (sans authentification)
    // ===================================

    // Authentication (limiteur 'auth' défini dans bootstrap/app.php)
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Catégories
    Route::get('accueil', AccueilController::class);
    Route::get('categories', [CategorieController::class, 'index']);
    Route::get('categories/{slug}', [CategorieController::class, 'show']);

    // Produits
    Route::prefix('produits')->group(function () {
        Route::get('/', [ProduitController::class, 'index']);
        Route::get('/{slug}', [ProduitController::class, 'show']);
        Route::get('/{slug}/similar', [ProduitController::class, 'similar']);
    });

    // Livraison : quartiers par ville (adresses), villes et minimum d'un vendeur
    Route::get('livraison/quartiers', [LivraisonController::class, 'quartiers']);
    Route::get('livraison/vendeur/{id}', [LivraisonController::class, 'vendeur'])->whereNumber('id');

    // Pages publiques des vendeurs
    Route::get('vendeurs/{id}', [VendeurController::class, 'show'])->whereNumber('id');

    // Webhook NotchPay (signature vérifiée dans le contrôleur)
    Route::post('webhooks/notchpay', [PaiementController::class, 'webhook']);

    // ===================================
    // ROUTES PROTÉGÉES (authentification requise)
    // ===================================

    Route::middleware(['auth:sanctum', 'actif'])->group(function () {

        // Authentication
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('user', [AuthController::class, 'user']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
        });

        Route::middleware('client')->group(function () {
            // Panier (client uniquement)
            Route::prefix('panier')->group(function () {
                Route::get('/', [PanierController::class, 'index']);
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
                Route::get('/{id}/facture', [FactureController::class, 'client'])->whereNumber('id');
                Route::post('/{id}/payer', [PaiementController::class, 'payer'])->whereNumber('id');
            });

            // Retour du client depuis la page de paiement NotchPay
            Route::get('paiements/{reference}', [PaiementController::class, 'show']);
        });

        // Adresses
        Route::prefix('adresses')->group(function () {
            Route::get('/', [AdresseController::class, 'index']);
            Route::post('/', [AdresseController::class, 'store']);
            Route::get('/{id}', [AdresseController::class, 'show']);
            Route::put('/{id}', [AdresseController::class, 'update']);
            Route::delete('/{id}', [AdresseController::class, 'destroy']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
            // Avant /{id} : sinon "clear-read" serait pris pour un identifiant
            Route::delete('/clear-read', [NotificationController::class, 'clearRead']);
            Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->whereNumber('id');
            Route::delete('/{id}', [NotificationController::class, 'destroy'])->whereNumber('id');
        });
    });

    // ===================================
    // ROUTES ADMIN (authentification + rôle admin)
    // ===================================

    Route::middleware(['auth:sanctum', 'actif', 'admin'])->prefix('admin')->group(function () {

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

        // Réglages de la boutique (durée du panier, frais de livraison, conditions vendeurs)
        Route::get('reglages', [AdminReglagesController::class, 'show']);
        Route::put('reglages', [AdminReglagesController::class, 'update']);

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
        });

        // Gestion des commandes
        Route::prefix('commandes')->group(function () {
            Route::get('/', [AdminCommandeController::class, 'index']);
            Route::get('/{id}', [AdminCommandeController::class, 'show']);
            Route::patch('/{id}/status', [AdminCommandeController::class, 'updateStatus']);
            Route::post('/{id}/confirm-payment', [AdminCommandeController::class, 'confirmPayment']);
            Route::get('/{id}/facture', [FactureController::class, 'admin'])->whereNumber('id');
        });

        // Gestion des utilisateurs
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index']);
            Route::get('/{id}', [AdminUserController::class, 'show']);
            Route::patch('/{id}/status', [AdminUserController::class, 'updateStatus']);
            Route::patch('/{id}/role', [AdminUserController::class, 'updateRole']);
            Route::patch('/{id}/vedette', [AdminUserController::class, 'updateVedette']);
        });

        // Rapports mensuels des vendeurs (aperçu JSON, PDF, CSV)
        Route::get('rapports', [AdminRapportController::class, 'index']);
        Route::get('rapports/mensuel', [AdminRapportController::class, 'mensuel']);

        // Quartiers proposés dans les adresses
        Route::apiResource('quartiers', AdminQuartierController::class);
    });

    // ===================================
    // ROUTES VENDEUR (authentification + rôle vendeur)
    // ===================================

    Route::middleware(['auth:sanctum', 'actif', 'vendeur'])->prefix('vendeur')->group(function () {
        // Profil boutique : accessible même incomplet (c'est là qu'on le complète)
        Route::get('profil', [VendeurProfilBoutiqueController::class, 'show']);
        Route::put('profil', [VendeurProfilBoutiqueController::class, 'update']);

        // Tout le reste exige un profil boutique complet
        Route::middleware('profil.vendeur')->group(function () {
            // Ma livraison : villes livrées et montant minimum
            Route::get('livraison', [VendeurLivraisonController::class, 'show']);
            Route::put('livraison', [VendeurLivraisonController::class, 'update']);

            // Gestion des commandes du vendeur (uniquement ses propres produits)
            Route::prefix('commandes')->group(function () {
                Route::get('/', [VendeurCommandeController::class, 'index']);
                Route::get('/{id}', [VendeurCommandeController::class, 'show']);
                Route::patch('/{id}/status', [VendeurCommandeController::class, 'updateStatus']);
                Route::post('/{id}/confirm-payment', [VendeurCommandeController::class, 'confirmPayment']);
                Route::get('/{id}/facture', [FactureController::class, 'vendeur'])->whereNumber('id');
            });

            // Statistiques du vendeur
            Route::get('stats', [VendeurCommandeController::class, 'stats']);

            // Ajouts catalogue autorisés pour le vendeur
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
            });
        });
    });
});
