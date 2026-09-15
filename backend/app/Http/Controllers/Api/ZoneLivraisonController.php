<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Panier;
use App\Models\Quartier;
use App\Models\User;
use App\Models\VendeurTarifLivraison;
use App\Models\ZoneLivraison;
use App\Services\LivraisonVendeur;
use Illuminate\Http\Request;

class ZoneLivraisonController extends Controller
{
    public function allQuartiers(Request $request)
    {
        $validated = $request->validate([
            'ville' => 'nullable|in:yaoundé,douala',
        ]);

        $quartiers = Quartier::where('actif', true)
            ->when(isset($validated['ville']), fn ($query) => $query->where('ville', $validated['ville']))
            ->orderBy('ville')
            ->orderBy('nom')
            ->get()
            ->groupBy('ville');

        return response()->json([
            'success' => true,
            'data' => [
                'yaoundé' => $quartiers->get('yaoundé', collect())->values(),
                'douala' => $quartiers->get('douala', collect())->values(),
            ],
        ]);
    }

    /**
     * Livraison d'un vendeur, publique (aperçu du panier et du checkout) : frais standard,
     * minimum d'achat et quartiers desservis. Le checkout recalcule tout côté serveur.
     */
    public function quartiersByVendeur(Request $request, int $vendeurId)
    {
        $vendeur = User::vendeurs()->find($vendeurId);

        $quartiers = VendeurTarifLivraison::where('vendeur_id', $vendeurId)
            ->where('actif', true)
            ->with(['quartier' => fn ($query) => $query->where('actif', true)])
            ->get()
            ->filter(fn (VendeurTarifLivraison $desserte) => $desserte->quartier !== null)
            ->map(function (VendeurTarifLivraison $desserte) {
                return [
                    'id' => $desserte->quartier->id,
                    'nom' => $desserte->quartier->nom,
                    'ville' => $desserte->quartier->ville,
                    'delai_min' => $desserte->delai_min,
                    'delai_max' => $desserte->delai_max,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'montant_minimum_livraison' => (float) ($vendeur?->montant_minimum_livraison ?? 0),
                'frais_livraison' => LivraisonVendeur::fraisStandard(),
                'quartiers' => $quartiers,
            ],
        ]);
    }

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
     * Zones actives d'une ville pour une commande client:
     * uniquement les zones créées par le livreur des produits du panier.
     */
    public function byCityForCommande(Request $request, $ville)
    {
        $ville = trim($ville);
        Panier::purgerExpires($request->user()->id);

        $panierItems = Panier::where('user_id', $request->user()->id)
            ->nonExpire()
            ->with(['produit:id,created_by_user_id'])
            ->get();

        if ($panierItems->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $creatorIds = $panierItems
            ->pluck('produit.created_by_user_id')
            ->filter()
            ->unique()
            ->values();

        if ($creatorIds->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => 'Votre panier doit contenir uniquement des produits d\'un même livreur.',
            ], 422);
        }

        if ($creatorIds->isEmpty()) {
            $zones = ZoneLivraison::active()
                ->where('ville', 'like', "%{$ville}%")
                ->orderBy('nom_zone')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $zones,
            ]);
        }

        $livreurId = (int) $creatorIds->first();

        $allItemsOwnedByCreator = $panierItems->every(function ($item) use ($livreurId) {
            return (int) ($item->produit->created_by_user_id ?? 0) === $livreurId;
        });

        if (! $allItemsOwnedByCreator) {
            return response()->json([
                'success' => false,
                'message' => 'Votre panier contient des produits non attribués à ce livreur.',
            ], 422);
        }

        $zones = ZoneLivraison::active()
            ->where('created_by_user_id', $livreurId)
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

        if (! $zone) {
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
