<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Adresse;
use App\Models\Quartier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdresseController extends Controller
{
    /**
     * Liste des adresses de l'utilisateur
     */
    public function index(Request $request)
    {
        $adresses = Adresse::where('user_id', $request->user()->id)
            ->with('quartierLivraison')
            ->orderBy('est_principale', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $adresses,
        ]);
    }

    /**
     * Ajouter une nouvelle adresse : ville, puis quartier de cette ville (obligatoires),
     * zone libre facultative
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->regles($request), $this->messages());

        $validated = $this->completerDepuisQuartier($validated);
        $validated['user_id'] = $request->user()->id;

        $adresse = Adresse::create($validated);

        // Adresse principale demandée, ou première adresse du client
        if (($validated['est_principale'] ?? false) || Adresse::where('user_id', $request->user()->id)->count() === 1) {
            $adresse->definirCommePrincipale();
        }

        return response()->json([
            'success' => true,
            'message' => 'Adresse ajoutée avec succès',
            'data' => $adresse->load('quartierLivraison'),
        ], 201);
    }

    /**
     * Afficher une adresse
     */
    public function show(Request $request, $id)
    {
        $adresse = Adresse::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with('quartierLivraison')
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

        // Changer de quartier impose d'indiquer sa ville (le quartier doit lui appartenir)
        $validated = $request->validate($this->regles($request, $adresse), $this->messages());
        $validated = $this->completerDepuisQuartier($validated);

        $adresse->update($validated);

        if ($validated['est_principale'] ?? false) {
            $adresse->definirCommePrincipale();
        }

        return response()->json([
            'success' => true,
            'message' => 'Adresse mise à jour',
            'data' => $adresse->load('quartierLivraison'),
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

    private function regles(Request $request, ?Adresse $adresse = null): array
    {
        $creation = $adresse === null;
        $obligatoire = $creation ? 'required' : 'required_with:quartier_id';

        return [
            'libelle' => 'sometimes|nullable|string|max:100',
            'ville' => [$obligatoire, 'string', Quartier::regleVille()],
            'quartier_id' => [
                $creation ? 'required' : 'sometimes',
                'integer',
                Rule::exists('quartiers', 'id')
                    ->where('actif', true)
                    ->where('ville', $request->input('ville', $adresse?->ville)),
            ],
            'zone' => 'sometimes|nullable|string|max:150',
            'telephone_contact' => ($creation ? 'required' : 'sometimes').'|string|regex:/^\+237[0-9]{9}$/',
            'point_repere' => 'sometimes|nullable|string|max:255',
            'complement_adresse' => 'sometimes|nullable|string|max:500',
            'est_principale' => 'boolean',
        ];
    }

    private function messages(): array
    {
        return [
            'ville.required' => 'Choisissez votre ville.',
            'ville.required_with' => 'Choisissez la ville du quartier.',
            'ville.in' => 'Nous livrons uniquement à Yaoundé et à Douala.',
            'quartier_id.required' => 'Choisissez votre quartier.',
            'quartier_id.exists' => 'Ce quartier n\'existe pas dans la ville choisie.',
        ];
    }

    // Le nom du quartier et la ville sont repris du quartier choisi (et non du texte envoyé)
    private function completerDepuisQuartier(array $validated): array
    {
        if (isset($validated['quartier_id'])) {
            $quartier = Quartier::findOrFail($validated['quartier_id']);
            $validated['quartier'] = $quartier->nom;
            $validated['ville'] = $quartier->ville;
        } else {
            unset($validated['ville']);
        }

        return $validated;
    }
}
