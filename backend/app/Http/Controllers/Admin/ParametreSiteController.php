<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParametreSite;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParametreSiteController extends Controller
{
    /**
     * Liste des paramètres (admin)
     */
    public function index(Request $request)
    {
        $query = ParametreSite::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cle', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('groupe', 'like', "%{$search}%");
            });
        }

        if ($request->has('groupe')) {
            $query->where('groupe', $request->groupe);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $parametres = $query->orderBy('groupe', 'asc')
            ->orderBy('cle', 'asc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $parametres,
        ]);
    }

    /**
     * Créer un paramètre
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cle' => 'required|string|max:255|unique:parametre_sites,cle',
            'valeur' => 'nullable',
            'type' => 'required|in:string,integer,boolean,json',
            'description' => 'nullable|string',
            'groupe' => 'nullable|string|max:100',
        ]);

        $validated['valeur'] = $this->normalizeValue(
            $validated['type'],
            $validated['valeur'] ?? null
        );

        $parametre = ParametreSite::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Paramètre créé avec succès',
            'data' => $parametre,
        ], 201);
    }

    /**
     * Détail d'un paramètre
     */
    public function show($id)
    {
        $parametre = ParametreSite::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $parametre,
        ]);
    }

    /**
     * Mettre à jour un paramètre
     */
    public function update(Request $request, $id)
    {
        $parametre = ParametreSite::findOrFail($id);

        $validated = $request->validate([
            'cle' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('parametre_sites', 'cle')->ignore($parametre->id),
            ],
            'valeur' => 'nullable',
            'type' => 'sometimes|in:string,integer,boolean,json',
            'description' => 'nullable|string',
            'groupe' => 'nullable|string|max:100',
        ]);

        $type = $validated['type'] ?? $parametre->type;
        if (array_key_exists('valeur', $validated)) {
            $validated['valeur'] = $this->normalizeValue($type, $validated['valeur']);
        }

        $parametre->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Paramètre mis à jour',
            'data' => $parametre,
        ]);
    }

    /**
     * Supprimer un paramètre
     */
    public function destroy($id)
    {
        $parametre = ParametreSite::findOrFail($id);
        $parametre->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paramètre supprimé',
        ]);
    }

    private function normalizeValue(string $type, $value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        switch ($type) {
            case 'integer':
                return (string) ((int) $value);
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            case 'json':
                if (is_array($value)) {
                    return json_encode($value);
                }
                if (is_string($value)) {
                    json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        abort(422, 'Valeur JSON invalide');
                    }
                    return $value;
                }
                return json_encode($value);
            default:
                return (string) $value;
        }
    }
}
