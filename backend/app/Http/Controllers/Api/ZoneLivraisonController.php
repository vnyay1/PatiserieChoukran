<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ZoneLivraison;
use Illuminate\Http\Request;

class ZoneLivraisonController extends Controller
{
    /**
     * Liste des zones de livraison actives
     */
    public function index()
    {
        $zones = ZoneLivraison::active()
            ->orderBy('ville')
            ->orderBy('nom_zone')
            ->get()
            ->groupBy('ville');

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    /**
     * Zones pour une ville spécifique
     */
    public function byCity($ville)
    {
        $ville = trim($ville);
        $zones = ZoneLivraison::active()
            ->where('ville', 'like', "%{$ville}%")
            ->orderBy('nom_zone')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    /**
     * Chercher une zone par quartier
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'ville' => 'required|string',
            'quartier' => 'required|string',
        ]);

        $zone = ZoneLivraison::where('ville', 'like', "%{$validated['ville']}%")
            ->where('nom_zone', 'like', "%{$validated['quartier']}%")
            ->active()
            ->first();

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Zone de livraison non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $zone,
        ]);
    }
}
