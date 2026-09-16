<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use App\Models\Quartier;
use App\Services\LivraisonVendeur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * « Ma livraison » : villes livrées et montant d'achat minimum du vendeur.
 * Les frais sont fixés par la plateforme (LivraisonVendeur::fraisStandard()).
 */
class LivraisonController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->reglages($request),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'montant_minimum_livraison' => 'required|numeric|min:0|max:1000000',
            'villes' => 'present|array',
            'villes.*' => 'distinct|'.Quartier::regleVille(),
        ], [
            'villes.*.in' => 'Ville inconnue.',
        ]);

        $vendeur = $request->user();

        DB::transaction(function () use ($vendeur, $validated) {
            $vendeur->update(['montant_minimum_livraison' => $validated['montant_minimum_livraison']]);

            $vendeur->villesLivraison()->whereNotIn('ville', $validated['villes'])->delete();
            foreach ($validated['villes'] as $ville) {
                $vendeur->villesLivraison()->firstOrCreate(['ville' => $ville]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Réglages de livraison enregistrés',
            'data' => $this->reglages($request),
        ]);
    }

    private function reglages(Request $request): array
    {
        $vendeur = $request->user()->load('villesLivraison');

        return [
            'montant_minimum_livraison' => (float) $vendeur->montant_minimum_livraison,
            'frais_livraison_standard' => LivraisonVendeur::fraisStandard(),
            'villes' => $vendeur->villesLivrees(),
            'villes_disponibles' => Quartier::VILLES,
        ];
    }
}
