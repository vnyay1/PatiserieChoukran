<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use App\Models\Quartier;
use App\Models\VendeurTarifLivraison;
use App\Services\LivraisonVendeur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Livraison d'un vendeur : quartiers desservis (avec délais) et montant d'achat minimum.
 * Les frais sont fixés par la plateforme (LivraisonVendeur::fraisStandard()).
 */
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
            'meta' => [
                'montant_minimum_livraison' => (float) $request->user()->montant_minimum_livraison,
                'frais_livraison_standard' => LivraisonVendeur::fraisStandard(),
            ],
        ]);
    }

    /**
     * Montant d'achat minimum (produits de ce vendeur) pour accepter une livraison.
     * 0 = pas de minimum. Le retrait en boutique n'est pas concerné.
     */
    public function updateMinimum(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'montant_minimum_livraison' => 'required|numeric|min:0|max:1000000',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Montant minimum de livraison enregistré',
            'data' => [
                'montant_minimum_livraison' => (float) $request->user()->montant_minimum_livraison,
            ],
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
            'message' => 'Quartier desservi ajouté',
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
            'message' => 'Quartier desservi mis à jour',
            'data' => $tarif->load('quartier'),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tarif = $this->findTarifForVendeur($request, $id);
        $tarif->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quartier desservi retiré',
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
