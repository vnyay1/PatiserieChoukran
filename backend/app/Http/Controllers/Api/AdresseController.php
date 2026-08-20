<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Adresse;
use App\Models\Quartier;
use Illuminate\Http\Request;

class AdresseController extends Controller
{
    /**
     * Liste des adresses de l'utilisateur
     */
    public function index(Request $request)
    {
        $adresses = Adresse::where('user_id', $request->user()->id)
            ->with(['zoneLivraison', 'quartier'])
            ->orderBy('est_principale', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $adresses,
        ]);
    }

    /**
     * Ajouter une nouvelle adresse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'libelle' => 'nullable|string|max:100',
            'quartier_id' => 'required|exists:quartiers,id',
            'quartier' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'zone_livraison_id' => 'nullable|exists:zone_livraisons,id,est_active,1',
            'telephone_contact' => 'required|string|regex:/^\+237[0-9]{9}$/',
            'point_repere' => 'nullable|string',
            'complement_adresse' => 'nullable|string',
            'est_principale' => 'boolean',
        ]);

        $quartier = Quartier::findOrFail($validated['quartier_id']);
        $validated['quartier'] = $validated['quartier'] ?? $quartier->nom;
        $validated['ville'] = $validated['ville'] ?? $quartier->ville;
        $validated['user_id'] = $request->user()->id;

        $adresse = Adresse::create($validated);

        // Si c'est la première adresse ou si elle est définie comme principale
        if ($validated['est_principale'] ?? false) {
            $adresse->definirCommePrincipale();
        }

        // Si c'est la première adresse, la définir comme principale
        if (Adresse::where('user_id', $request->user()->id)->count() === 1) {
            $adresse->definirCommePrincipale();
        }

        return response()->json([
            'success' => true,
            'message' => 'Adresse ajoutée avec succès',
            'data' => $adresse->load(['zoneLivraison', 'quartier']),
        ], 201);
    }

    /**
     * Afficher une adresse
     */
    public function show(Request $request, $id)
    {
        $adresse = Adresse::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with(['zoneLivraison', 'quartier'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $adresse,
        ]);
    }

    /**
     * Mettre à jour une adresse
     */
    public function update(Request $request, $id)
    {
        $adresse = Adresse::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'libelle' => 'sometimes|string|max:100',
            'quartier_id' => 'sometimes|exists:quartiers,id',
            'quartier' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'zone_livraison_id' => 'nullable|exists:zone_livraisons,id,est_active,1',
            'telephone_contact' => 'sometimes|string|regex:/^\+237[0-9]{9}$/',
            'point_repere' => 'nullable|string',
            'complement_adresse' => 'nullable|string',
            'est_principale' => 'boolean',
        ]);

        if (isset($validated['quartier_id'])) {
            $quartier = Quartier::findOrFail($validated['quartier_id']);
            $validated['quartier'] = $validated['quartier'] ?? $quartier->nom;
            $validated['ville'] = $validated['ville'] ?? $quartier->ville;
        }

        $shouldGeocode = false;
        if (array_key_exists('quartier', $validated) && $validated['quartier'] !== $adresse->quartier) {
            $shouldGeocode = true;
        }
        if (array_key_exists('ville', $validated) && $validated['ville'] !== $adresse->ville) {
            $shouldGeocode = true;
        }

        if ($shouldGeocode && (!array_key_exists('latitude', $validated) || $validated['latitude'] === null
            || !array_key_exists('longitude', $validated) || $validated['longitude'] === null)) {
            $quartier = $validated['quartier'] ?? $adresse->quartier;
            $ville = $validated['ville'] ?? $adresse->ville;
        }

        $adresse->update($validated);

        if ($validated['est_principale'] ?? false) {
            $adresse->definirCommePrincipale();
        }

        return response()->json([
            'success' => true,
            'message' => 'Adresse mise à jour',
            'data' => $adresse->load(['zoneLivraison', 'quartier']),
        ]);
    }

    /**
     * Supprimer une adresse
     */
    public function destroy(Request $request, $id)
    {
        $adresse = Adresse::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        // Vérifier si c'est l'adresse principale
        $estPrincipale = $adresse->est_principale;

        $adresse->delete();

        // Si c'était l'adresse principale, définir une autre comme principale
        if ($estPrincipale) {
            $nouvelleAdressePrincipale = Adresse::where('user_id', $request->user()->id)->first();
            if ($nouvelleAdressePrincipale) {
                $nouvelleAdressePrincipale->definirCommePrincipale();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Adresse supprimée',
        ]);
    }

    /**
     * Définir une adresse comme principale
     */
    public function setPrincipal(Request $request, $id)
    {
        $adresse = Adresse::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $adresse->definirCommePrincipale();

        return response()->json([
            'success' => true,
            'message' => 'Adresse définie comme principale',
            'data' => $adresse,
        ]);
    }
}
