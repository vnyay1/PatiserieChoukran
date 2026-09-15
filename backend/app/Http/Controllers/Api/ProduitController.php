<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    // Colonnes autorisées pour le tri : tout le reste est refusé par la validation
    private const TRIS = ['created_at', 'prix', 'nombre_commandes', 'nom'];

    /**
     * Liste des produits avec filtres
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'categorie_id' => 'nullable|integer',
            'vendeur_id' => 'nullable|integer',
            'search' => 'nullable|string|max:100',
            'prix_min' => 'nullable|numeric|min:0',
            'prix_max' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|in:'.implode(',', self::TRIS),
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        // Seuls le nom, le logo et la mise en avant du vendeur sont exposés (pas son téléphone)
        $query = Produit::with(['categorie', Produit::VENDEUR_PUBLIC])->visible();

        if (! empty($validated['categorie_id'])) {
            $query->where('categorie_id', $validated['categorie_id']);
        }

        // Page publique d'un vendeur
        if (! empty($validated['vendeur_id'])) {
            $query->where('created_by_user_id', $validated['vendeur_id']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('vedette')) {
            $query->vedette();
        }

        if ($request->boolean('promotion')) {
            $query->promotion();
        }

        // Prix réellement payé : le prix promo s'il existe, sinon le prix unitaire.
        // Liaison en entier (FCFA sans centimes) : une chaîne serait mal comparée par SQLite.
        if (isset($validated['prix_min'])) {
            $query->whereRaw('COALESCE(prix_promo, prix_unitaire) >= ?', [(int) floor($validated['prix_min'])]);
        }
        if (isset($validated['prix_max'])) {
            $query->whereRaw('COALESCE(prix_promo, prix_unitaire) <= ?', [(int) ceil($validated['prix_max'])]);
        }

        // Les produits des vendeurs vedettes restent en tête, quel que soit le tri choisi
        $query->vendeursVedettesEnTete();

        // Tri (colonne et sens validés ci-dessus)
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';

        if ($sortBy === 'prix') {
            $query->orderByRaw('COALESCE(prix_promo, prix_unitaire) '.($sortOrder === 'asc' ? 'asc' : 'desc'));
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }
        // Ordre stable d'une page à l'autre en cas d'égalité
        $query->orderBy('id', 'desc');

        $produits = $query->paginate($validated['per_page'] ?? 12);

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
            ->with(['categorie', Produit::VENDEUR_PUBLIC])
            ->visible()
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
            ->with(Produit::VENDEUR_PUBLIC)
            ->visible()
            ->vendeursVedettesEnTete()
            ->limit(4)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $similaires,
        ]);
    }

    /**
     * Produits vedettes pour la page d'accueil : ceux des vendeurs mis en avant
     */
    public function featured()
    {
        $produits = Produit::vedette()
            ->visible()
            ->with(['categorie', Produit::VENDEUR_PUBLIC])
            ->orderBy('nombre_commandes', 'desc')
            ->orderBy('id', 'desc')
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
        $produits = Produit::visible()
            ->with(['categorie', Produit::VENDEUR_PUBLIC])
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
            ->visible()
            ->with(['categorie', Produit::VENDEUR_PUBLIC])
            ->vendeursVedettesEnTete()
            ->orderBy('created_at', 'desc')
            ->limit(24)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $produits,
        ]);
    }
}
