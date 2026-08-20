<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZoneLivraison;
use Illuminate\Http\Request;

class ZoneLivraisonController extends Controller
{
    /**
     * Liste des zones de livraison (admin)
     */
    public function index(Request $request)
    {
        $query = ZoneLivraison::query();

        if ($this->isVendeur($request)) {
            $query->where('created_by_user_id', $request->user()->id);
        }

        if ($request->has('ville')) {
            $query->where('ville', $request->ville);
        }

        if ($request->has('est_active')) {
            $query->where('est_active', $request->boolean('est_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom_zone', 'like', "%{$search}%")
                  ->orWhere('ville', 'like', "%{$search}%");
            });
        }

        $zones = $query->orderBy('ville')
            ->orderBy('nom_zone')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    /**
     * Créer une zone de livraison
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_zone' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'tarif_livraison' => 'required|numeric|min:0',
            'delai_livraison_min' => 'required|integer|min:0',
            'delai_livraison_max' => 'required|integer|min:0|gte:delai_livraison_min',
            'est_active' => 'boolean',
        ]);

        $validated['created_by_user_id'] = $request->user()->id;

        $zone = ZoneLivraison::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Zone créée avec succès',
            'data' => $zone,
        ], 201);
    }

    /**
     * Afficher une zone
     */
    public function show(Request $request, $id)
    {
        $zone = $this->findZoneForManagement($request, $id);

        return response()->json([
            'success' => true,
            'data' => $zone,
        ]);
    }

    /**
     * Mettre à jour une zone
     */
    public function update(Request $request, $id)
    {
        $zone = $this->findZoneForManagement($request, $id);

        $validated = $request->validate([
            'nom_zone' => 'sometimes|string|max:255',
            'ville' => 'sometimes|string|max:255',
            'tarif_livraison' => 'sometimes|numeric|min:0',
            'delai_livraison_min' => 'sometimes|integer|min:0',
            'delai_livraison_max' => 'sometimes|integer|min:0',
            'est_active' => 'boolean',
        ]);

        if (array_key_exists('delai_livraison_min', $validated) || array_key_exists('delai_livraison_max', $validated)) {
            $min = $validated['delai_livraison_min'] ?? $zone->delai_livraison_min;
            $max = $validated['delai_livraison_max'] ?? $zone->delai_livraison_max;

            if ($max < $min) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le délai max doit être supérieur ou égal au délai min.',
                ], 422);
            }
        }

        $zone->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Zone mise à jour',
            'data' => $zone,
        ]);
    }

    /**
     * Supprimer une zone
     */
    public function destroy(Request $request, $id)
    {
        $zone = $this->findZoneForManagement($request, $id);
        $zone->delete();

        return response()->json([
            'success' => true,
            'message' => 'Zone supprimée',
        ]);
    }

    private function findZoneForManagement(Request $request, $id): ZoneLivraison
    {
        return ZoneLivraison::query()
            ->when($this->isVendeur($request), function ($query) use ($request) {
                $query->where('created_by_user_id', $request->user()->id);
            })
            ->findOrFail($id);
    }

    private function isVendeur(Request $request): bool
    {
        return $request->user()?->role === 'vendeur';
    }
}
