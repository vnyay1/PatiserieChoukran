<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    /**
     * Liste des produits avec filtres
     */
    public function index(Request $request)
    {
        $query = Produit::with('categorie')->disponible();

        // Filtre par catégorie
        if ($request->has('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        // Filtre par recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtre produits vedettes
        if ($request->boolean('vedette')) {
            $query->vedette();
        }

        // Filtre produits en promotion
        if ($request->boolean('promotion')) {
            $query->promotion();
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if ($sortBy === 'prix') {
            $query->orderByRaw('COALESCE(prix_promo, prix_unitaire) ' . $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $produits = $query->paginate($request->get('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $produits,
        ]);
    }

    /**
     * Afficher un produit
     */
    public function show($slug)
    {
        $produit = Produit::where('slug', $slug)
            ->with('categorie')
            ->disponible()
            ->firstOrFail();

        // Incrémenter le nombre de vues
        $produit->incrementerVues();

        return response()->json([
            'success' => true,
            'data' => $produit,
        ]);
    }

    /**
     * Produits similaires
     */
    public function similar($slug)
    {
        $produit = Produit::where('slug', $slug)->firstOrFail();

        $similaires = Produit::where('categorie_id', $produit->categorie_id)
            ->where('id', '!=', $produit->id)
            ->disponible()
            ->limit(4)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $similaires,
        ]);
    }

    /**
     * Produits vedettes pour la page d'accueil
     */
    public function featured()
    {
        $produits = Produit::vedette()
            ->disponible()
            ->with('categorie')
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $produits,
        ]);
    }

    /**
     * Nouveautés
     */
    public function nouveautes()
    {
        $produits = Produit::disponible()
            ->with('categorie')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $produits,
        ]);
    }

    /**
     * Promotions
     */
    public function promotions()
    {
        $produits = Produit::promotion()
            ->disponible()
            ->with('categorie')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $produits,
        ]);
    }
}