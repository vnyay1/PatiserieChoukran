<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;
use App\Models\Quartier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Page d'accueil en un seul appel (catégories, vendeurs vedettes, nouveautés) au lieu de trois.
 */
class AccueilController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $ville = $request->validate(['ville' => 'nullable|'.Quartier::regleVille()])['ville'] ?? null;

        $categories = Categorie::actif()
            ->ordreDaffichage()
            // Même périmètre que la boutique (vendeurs actifs au profil complet)
            ->withCount(['produitsDisponibles' => fn ($query) => $query->visible()->livrableDans($ville)])
            ->get();

        // Produits des vendeurs mis en avant par l'admin
        $vedettes = Produit::vedette()
            ->visible()
            ->livrableDans($ville)
            ->with(['categorie', Produit::VENDEUR_PUBLIC])
            ->orderBy('nombre_commandes', 'desc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        $nouveautes = Produit::visible()
            ->livrableDans($ville)
            ->with(['categorie', Produit::VENDEUR_PUBLIC])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => compact('categories', 'vedettes', 'nouveautes'),
        ]);
    }
}
