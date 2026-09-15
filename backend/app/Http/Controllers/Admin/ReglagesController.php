<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Panier;
use App\Models\ParametreSite;
use App\Services\LivraisonVendeur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Réglages de la boutique modifiables par l'admin sans connaître les clés techniques
 * des paramètres : durée du panier, frais de livraison, conditions des vendeurs.
 */
class ReglagesController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->reglages(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // De 5 minutes à 30 jours
            'panier_duree_minutes' => 'sometimes|integer|min:5|max:43200',
            'frais_livraison_standard' => 'sometimes|integer|min:0|max:100000',
            'conditions_vendeur' => 'sometimes|string|min:20|max:10000',
        ], [
            'panier_duree_minutes.min' => 'Le panier doit rester au moins 5 minutes.',
            'panier_duree_minutes.max' => 'Le panier ne peut pas être conservé plus de 30 jours.',
        ]);

        foreach ($validated as $cle => $valeur) {
            ParametreSite::set($cle, (string) $valeur, is_int($valeur) ? 'integer' : 'string');
        }

        return response()->json([
            'success' => true,
            'message' => 'Réglages enregistrés',
            'data' => $this->reglages(),
        ]);
    }

    private function reglages(): array
    {
        return [
            'panier_duree_minutes' => Panier::dureeMinutes(),
            'frais_livraison_standard' => (int) LivraisonVendeur::fraisStandard(),
            'conditions_vendeur' => (string) ParametreSite::get('conditions_vendeur', ''),
        ];
    }
}
