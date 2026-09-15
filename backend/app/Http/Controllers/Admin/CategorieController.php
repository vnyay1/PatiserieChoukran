<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Services\Images;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategorieController extends Controller
{
    /**
     * Liste des catégories (admin + vendeur)
     */
    public function index(Request $request)
    {
        $query = Categorie::query()->with('createur:id,nom_complet');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('nom', 'like', "%{$search}%");
        }

        if ($request->has('est_actif')) {
            $query->where('est_actif', $request->boolean('est_actif'));
        }

        $categories = $query->orderBy('ordre_affichage', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Créer une catégorie
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'ordre_affichage' => 'nullable|integer|min:0',
            'est_actif' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = Images::enregistrer($request->file('image'), 'categories', 800);
        }

        $validated['slug'] = $this->generateUniqueSlug($validated['nom']);
        $validated['created_by_user_id'] = $request->user()->id;
        // Colonne non nullable (défaut 0) : un champ vide ne doit pas provoquer d'erreur SQL
        $validated['ordre_affichage'] = $validated['ordre_affichage'] ?? 0;

        $categorie = Categorie::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data' => $categorie,
        ], 201);
    }

    /**
     * Détail d'une catégorie
     */
    public function show(Request $request, $id)
    {
        $categorie = $this->findCategorieForManagement($request, $id);

        return response()->json([
            'success' => true,
            'data' => $categorie,
        ]);
    }

    /**
     * Mettre à jour une catégorie
     */
    public function update(Request $request, $id)
    {
        $categorie = $this->findCategorieForManagement($request, $id);

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'ordre_affichage' => 'nullable|integer|min:0',
            'est_actif' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($categorie->image) {
                \Storage::disk('public')->delete($categorie->image);
            }
            $validated['image'] = Images::enregistrer($request->file('image'), 'categories', 800);
        }

        if (isset($validated['nom']) && $validated['nom'] !== $categorie->nom) {
            $validated['slug'] = $this->generateUniqueSlug($validated['nom'], $categorie->id);
        }

        if (array_key_exists('ordre_affichage', $validated) && $validated['ordre_affichage'] === null) {
            $validated['ordre_affichage'] = 0;
        }

        $categorie->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie mise à jour',
            'data' => $categorie,
        ]);
    }

    /**
     * Supprimer une catégorie
     */
    public function destroy(Request $request, $id)
    {
        $categorie = $this->findCategorieForManagement($request, $id);

        // produits.categorie_id est en cascade : supprimer la catégorie supprimerait tous
        // ses produits, y compris ceux d'autres vendeurs et ceux déjà commandés.
        $nombreProduits = $categorie->produits()->count();
        if ($nombreProduits > 0) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer « {$categorie->nom} » : {$nombreProduits} produit(s) y sont rattachés. "
                    .'Déplacez-les dans une autre catégorie ou désactivez plutôt la catégorie.',
            ], 422);
        }

        if ($categorie->image) {
            \Storage::disk('public')->delete($categorie->image);
        }

        $categorie->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée',
        ]);
    }

    protected function generateUniqueSlug(string $nom, ?int $ignoreId = null): string
    {
        $base = Str::slug($nom);
        $slug = $base;
        $suffix = 2;

        while (
            Categorie::where('slug', $slug)
                ->when($ignoreId, function ($query) use ($ignoreId) {
                    $query->where('id', '!=', $ignoreId);
                })
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function findCategorieForManagement(Request $request, $id): Categorie
    {
        return Categorie::query()
            ->when($this->isVendeur($request), function ($query) use ($request) {
                $query->where('created_by_user_id', $request->user()->id);
            })
            ->findOrFail($id);
    }

    private function isVendeur(Request $request): bool
    {
        return $request->user()?->role === 'vendeur';
    }
}
