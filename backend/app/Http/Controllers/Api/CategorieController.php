<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;

class CategorieController extends Controller
{
    /**
     * Liste toutes les catégories actives
     */
    public function index()
    {
        $categories = Categorie::actif()
            ->ordreDaffichage()
            // Même périmètre que la boutique (vendeurs actifs au profil complet)
            ->withCount(['produitsDisponibles' => fn ($query) => $query->visible()])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Afficher une catégorie avec ses produits
     */
    public function show($slug)
    {
        $categorie = Categorie::where('slug', $slug)
            ->actif()
            ->with(['produitsDisponibles' => function ($query) {
                $query->visible()
                    ->with(Produit::VENDEUR_PUBLIC)
                    ->vendeursVedettesEnTete()
                    ->orderBy('created_at', 'desc');
            }])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $categorie,
        ]);
    }
}
