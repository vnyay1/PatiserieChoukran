<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    /**
     * Liste toutes les catégories actives
     */
    public function index()
    {
        $categories = Categorie::actif()
            ->ordreDaffichage()
            ->withCount('produitsDisponibles')
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
            ->with(['produitsDisponibles' => function($query) {
                $query->orderBy('est_vedette', 'desc')
                      ->orderBy('created_at', 'desc');
            }])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $categorie,
        ]);
    }
}
