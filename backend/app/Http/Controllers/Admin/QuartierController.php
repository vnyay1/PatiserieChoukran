<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quartier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuartierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ville' => 'nullable|in:yaoundé,douala',
            'actif' => 'nullable|boolean',
            'search' => 'nullable|string',
        ]);

        $query = Quartier::query()
            ->withCount('adresses')
            ->when(isset($validated['ville']), fn ($q) => $q->where('ville', $validated['ville']))
            ->when(array_key_exists('actif', $validated), fn ($q) => $q->where('actif', $request->boolean('actif')))
            ->when(isset($validated['search']), function ($q) use ($validated) {
                $q->where('nom', 'like', '%'.$validated['search'].'%');
            });

        $quartiers = $query->orderBy('ville')
            ->orderBy('nom')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $quartiers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:150',
                Rule::unique('quartiers', 'nom')->where('ville', $request->input('ville')),
            ],
            'ville' => 'required|in:yaoundé,douala',
            'actif' => 'boolean',
        ]);

        $quartier = Quartier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Quartier créé',
            'data' => $quartier,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $quartier = Quartier::withCount('adresses')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $quartier,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $quartier = Quartier::findOrFail($id);

        $ville = $request->input('ville', $quartier->ville);
        $validated = $request->validate([
            'nom' => [
                'sometimes',
                'string',
                'max:150',
                Rule::unique('quartiers', 'nom')->where('ville', $ville)->ignore($quartier->id),
            ],
            'ville' => 'sometimes|in:yaoundé,douala',
            'actif' => 'boolean',
        ]);

        $quartier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Quartier mis à jour',
            'data' => $quartier,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $quartier = Quartier::findOrFail($id);

        if ($quartier->adresses()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce quartier car des adresses l’utilisent.',
            ], 422);
        }

        $quartier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quartier supprimé',
        ]);
    }
}
