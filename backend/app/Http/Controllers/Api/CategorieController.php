<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;
use App\Models\Quartier;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    /**
     * Liste toutes les catégories actives
     */
    public function index(Request $request)
    {
        $ville = $this->ville($request);

        $categories = Categorie::actif()
            ->ordreDaffichage()
            // Même périmètre que la boutique (vendeurs actifs au profil complet)
            ->withCount(['produitsDisponibles' => fn ($query) => $query->visible()->livrableDans($ville)])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Afficher une catégorie avec ses produits
     */
    public function show(Request $request, $slug)
    {
        $ville = $this->ville($request);

        $categorie = Categorie::where('slug', $slug)
            ->actif()
            ->with(['produitsDisponibles' => function ($query) use ($ville) {
                $query->visible()
                    ->livrableDans($ville)
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

    private function ville(Request $request): ?string
    {
        return $request->validate(['ville' => 'nullable|'.Quartier::regleVille()])['ville'] ?? null;
    }
}
