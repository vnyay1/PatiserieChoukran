<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParametreSite;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Pages publiques des vendeurs.
 */
class VendeurController extends Controller
{
    /**
     * Profil public : seulement pour un vendeur actif au profil boutique complet.
     */
    public function show(int $id): JsonResponse
    {
        $vendeur = User::vendeursEnActivite()->with('villesLivraison')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $vendeur->id,
                'nom_complet' => $vendeur->nom_complet,
                'email' => $vendeur->email,
                'logo_boutique' => $vendeur->logo_boutique,
                'description_boutique' => $vendeur->description_boutique,
                'est_vendeur_vedette' => $vendeur->est_vendeur_vedette,
                'montant_minimum_livraison' => (float) $vendeur->montant_minimum_livraison,
                'villes_livraison' => $vendeur->villesLivrees(),
                'membre_depuis' => $vendeur->created_at,
                'nombre_produits' => Produit::visible()->where('created_by_user_id', $vendeur->id)->count(),
            ],
        ]);
    }

    /**
     * Conditions que les vendeurs acceptent (texte modifiable par l'admin).
     */
    public function conditions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'conditions' => (string) ParametreSite::get('conditions_vendeur', ''),
            ],
        ]);
    }
}
