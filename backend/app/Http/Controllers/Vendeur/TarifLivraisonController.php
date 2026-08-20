<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use App\Models\Quartier;
use App\Models\VendeurTarifLivraison;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TarifLivraisonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tarifs = VendeurTarifLivraison::where('vendeur_id', $request->user()->id)
            ->with('quartier')
            ->orderByDesc('actif')
            ->orderBy('quartier_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tarifs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quartier_id' => [
                'required',
                Rule::exists('quartiers', 'id')->where('actif', true),
                Rule::unique('vendeur_tarifs_livraison', 'quartier_id')
                    ->where('vendeur_id', $request->user()->id),
            ],
            'tarif' => 'required|numeric|min:0',
            'delai_min' => 'required|integer|min:0',
            'delai_max' => 'required|integer|min:0|gte:delai_min',
            'actif' => 'boolean',
        ]);

        $tarif = VendeurTarifLivraison::create([
            ...$validated,
            'vendeur_id' => $request->user()->id,
        ])->load('quartier');

        return response()->json([
            'success' => true,
            'message' => 'Tarif de livraison créé',
            'data' => $tarif,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tarif = $this->findTarifForVendeur($request, $id)->load('quartier');

        return response()->json([
            'success' => true,
            'data' => $tarif,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tarif = $this->findTarifForVendeur($request, $id);

        $validated = $request->validate([
            'quartier_id' => [
                'sometimes',
                Rule::exists('quartiers', 'id')->where('actif', true),
                Rule::unique('vendeur_tarifs_livraison', 'quartier_id')
                    ->where('vendeur_id', $request->user()->id)
                    ->ignore($tarif->id),
            ],
            'tarif' => 'sometimes|numeric|min:0',
            'delai_min' => 'sometimes|integer|min:0',
            'delai_max' => 'sometimes|integer|min:0',
            'actif' => 'boolean',
        ]);

        $delaiMin = $validated['delai_min'] ?? $tarif->delai_min;
        $delaiMax = $validated['delai_max'] ?? $tarif->delai_max;

        if ($delaiMax < $delaiMin) {
            return response()->json([
                'success' => false,
                'message' => 'Le délai max doit être supérieur ou égal au délai min.',
            ], 422);
        }

        $tarif->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tarif de livraison mis à jour',
            'data' => $tarif->load('quartier'),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tarif = $this->findTarifForVendeur($request, $id);
        $tarif->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tarif de livraison supprimé',
        ]);
    }

    public function quartiers(Request $request): JsonResponse
    {
        $coveredQuartierIds = VendeurTarifLivraison::where('vendeur_id', $request->user()->id)
            ->pluck('quartier_id')
            ->all();

        $quartiers = Quartier::where('actif', true)
            ->orderBy('ville')
            ->orderBy('nom')
            ->get()
            ->map(function (Quartier $quartier) use ($coveredQuartierIds) {
                return [
                    'id' => $quartier->id,
                    'nom' => $quartier->nom,
                    'ville' => $quartier->ville,
                    'actif' => $quartier->actif,
                    'couvert' => in_array($quartier->id, $coveredQuartierIds, true),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $quartiers,
        ]);
    }

    private function findTarifForVendeur(Request $request, int $id): VendeurTarifLivraison
    {
        return VendeurTarifLivraison::where('vendeur_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();
    }
}
