<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Adresse;
use Illuminate\Http\Request;

class AdresseController extends Controller
{
    /**
     * Liste des adresses de l'utilisateur
     */
    public function index(Request $request)
    {
        $adresses = Adresse::where('user_id', $request->user()->id)
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
            'quartier' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'telephone_contact' => 'required|string|regex:/^\+237[0-9]{9}$/',
            'point_repere' => 'nullable|string',
            'complement_adresse' => 'nullable|string',
            'est_principale' => 'boolean',
        ]);

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
            'data' => $adresse,
        ], 201);
    }

    /**
     * Afficher une adresse
     */
    public function show(Request $request, $id)
    {
        $adresse = Adresse::where('user_id', $request->user()->id)
            ->where('id', $id)
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
            'quartier' => 'sometimes|string|max:255',
            'ville' => 'sometimes|string|max:255',
            'telephone_contact' => 'sometimes|string|regex:/^\+237[0-9]{9}$/',
            'point_repere' => 'nullable|string',
            'complement_adresse' => 'nullable|string',
            'est_principale' => 'boolean',
        ]);

        $adresse->update($validated);

        if ($validated['est_principale'] ?? false) {
            $adresse->definirCommePrincipale();
        }

        return response()->json([
            'success' => true,
            'message' => 'Adresse mise à jour',
            'data' => $adresse,
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
