<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quartier;
use App\Models\User;
use App\Services\LivraisonVendeur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Données publiques de livraison : villes et quartiers, conditions d'un vendeur.
 */
class LivraisonController extends Controller
{
    /**
     * Quartiers actifs regroupés par ville (formulaire d'adresse).
     */
    public function quartiers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ville' => 'nullable|'.Quartier::regleVille(),
        ]);

        $quartiers = Quartier::where('actif', true)
            ->when(isset($validated['ville']), fn ($query) => $query->where('ville', $validated['ville']))
            ->orderBy('nom')
            ->get(['id', 'nom', 'ville'])
            ->groupBy('ville');

        return response()->json([
            'success' => true,
            'data' => collect(Quartier::VILLES)
                ->mapWithKeys(fn ($libelle, $ville) => [$ville => $quartiers->get($ville, collect())->values()]),
        ]);
    }

    /**
     * Conditions de livraison d'un vendeur (aperçu du panier et du checkout) ;
     * le checkout les revérifie côté serveur.
     */
    public function vendeur(int $vendeurId): JsonResponse
    {
        $vendeur = User::vendeurs()->with('villesLivraison')->find($vendeurId);

        return response()->json([
            'success' => true,
            'data' => [
                'frais_livraison' => LivraisonVendeur::fraisStandard(),
                'montant_minimum_livraison' => (float) ($vendeur?->montant_minimum_livraison ?? 0),
                'villes' => $vendeur?->villesLivrees() ?? [],
            ],
        ]);
    }
}
