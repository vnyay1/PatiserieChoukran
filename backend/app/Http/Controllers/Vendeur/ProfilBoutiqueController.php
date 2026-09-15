<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use App\Models\ParametreSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Profil boutique du vendeur : obligatoire avant de vendre, visible sur sa page publique.
 */
class ProfilBoutiqueController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'profil' => $this->profil($request),
                'conditions' => (string) ParametreSite::get('conditions_vendeur', ''),
            ],
        ]);
    }

    /**
     * Envoyé en multipart (POST + _method=PUT) à cause du logo.
     */
    public function update(Request $request): JsonResponse
    {
        $vendeur = $request->user();

        if ($request->has('email')) {
            $request->merge(['email' => trim((string) $request->email)]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($vendeur->id)],
            'logo_boutique' => [
                $vendeur->logo_boutique ? 'nullable' : 'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
            'description_boutique' => 'required|string|min:30|max:2000',
            'conditions_acceptees' => 'accepted',
        ], [
            'logo_boutique.required' => 'Le logo de votre entreprise est obligatoire.',
            'description_boutique.min' => 'Décrivez votre entreprise en au moins 30 caractères.',
            'conditions_acceptees.accepted' => 'Vous devez accepter les conditions des vendeurs.',
        ]);

        $donnees = [
            'email' => $validated['email'],
            'description_boutique' => trim($validated['description_boutique']),
        ];

        if ($request->hasFile('logo_boutique')) {
            $ancienLogo = $vendeur->logo_boutique;
            $donnees['logo_boutique'] = $request->file('logo_boutique')->store('boutiques', 'public');

            if ($ancienLogo) {
                Storage::disk('public')->delete($ancienLogo);
            }
        }

        // Date de la première acceptation conservée
        if (! $vendeur->conditions_acceptees_le) {
            $donnees['conditions_acceptees_le'] = now();
        }

        $vendeur->update($donnees);

        return response()->json([
            'success' => true,
            'message' => 'Profil boutique enregistré',
            'data' => [
                'profil' => $this->profil($request),
                'user' => $vendeur->fresh(),
            ],
        ]);
    }

    private function profil(Request $request): array
    {
        $vendeur = $request->user();

        return [
            'id' => $vendeur->id,
            'nom_complet' => $vendeur->nom_complet,
            'email' => $vendeur->email,
            'logo_boutique' => $vendeur->logo_boutique,
            'description_boutique' => $vendeur->description_boutique,
            'conditions_acceptees_le' => $vendeur->conditions_acceptees_le,
            'profil_vendeur_complet' => $vendeur->estProfilVendeurComplet(),
        ];
    }
}
