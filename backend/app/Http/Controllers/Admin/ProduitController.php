<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProduitController extends Controller
{
    /**
     * Liste des produits (admin)
     */
    public function index(Request $request)
    {
        $query = Produit::with('categorie');

        if ($this->isLivreur($request)) {
            $query->where('created_by_user_id', $request->user()->id);
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('nom', 'like', "%{$search}%");
        }

        // Filtre par catégorie
        if ($request->has('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        // Filtre par disponibilité
        if ($request->has('est_disponible')) {
            $query->where('est_disponible', $request->boolean('est_disponible'));
        }

        $produits = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $produits,
        ]);
    }

    /**
     * Créer un produit
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categorie_id' => 'required|exists:categories,id',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix_unitaire' => 'required|numeric|min:0',
            'prix_promo' => 'nullable|numeric|min:0|lt:prix_unitaire',
            'promo_active' => 'sometimes|boolean',
            'image_principale' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images_secondaires.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'stock_disponible' => 'required|integer|min:0',
            'est_disponible' => 'boolean',
            'est_vedette' => 'boolean',
        ]);

        if ($request->has('promo_active')) {
            $promoActive = $request->boolean('promo_active');
            if (!$promoActive) {
                $validated['prix_promo'] = null;
            } elseif (!array_key_exists('prix_promo', $validated) || is_null($validated['prix_promo'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez renseigner un prix promo pour activer la promotion.',
                ], 422);
            }
        }

        // Upload image principale
        if ($request->hasFile('image_principale')) {
            $validated['image_principale'] = $request->file('image_principale')
                ->store('produits', 'public');
        }

        // Upload images secondaires
        if ($request->hasFile('images_secondaires')) {
            $imagesSecondaires = [];
            foreach ($request->file('images_secondaires') as $image) {
                $imagesSecondaires[] = $image->store('produits', 'public');
            }
            $validated['images_secondaires'] = $imagesSecondaires;
        }

        // Générer le slug
        $validated['slug'] = $this->generateUniqueSlug($validated['nom']);
        $validated['created_by_user_id'] = $request->user()->id;

        $produit = Produit::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'data' => $produit,
        ], 201);
    }

    /**
     * Afficher un produit
     */
    public function show(Request $request, $id)
    {
        $produit = $this->findProduitForManagement($request, $id)->load('categorie');

        return response()->json([
            'success' => true,
            'data' => $produit,
        ]);
    }

    /**
     * Mettre à jour un produit
     */
    public function update(Request $request, $id)
    {
        $produit = $this->findProduitForManagement($request, $id);

        $validated = $request->validate([
            'categorie_id' => 'sometimes|exists:categories,id',
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'prix_unitaire' => 'sometimes|numeric|min:0',
            'prix_promo' => 'nullable|numeric|min:0',
            'promo_active' => 'sometimes|boolean',
            'image_principale' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images_secondaires.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'stock_disponible' => 'sometimes|integer|min:0',
            'est_disponible' => 'boolean',
            'est_vedette' => 'boolean',
        ]);

        if ($request->has('promo_active') && !$request->boolean('promo_active')) {
            $validated['prix_promo'] = null;
        }

        if (array_key_exists('prix_promo', $validated)) {
            $prixBase = $validated['prix_unitaire'] ?? $produit->prix_unitaire;
            if (!is_null($validated['prix_promo']) && $validated['prix_promo'] >= $prixBase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le prix promo doit être inférieur au prix unitaire',
                ], 422);
            }
        }

        // Upload nouvelle image principale si fournie
        if ($request->hasFile('image_principale')) {
            // Supprimer l'ancienne image
            if ($produit->image_principale) {
                \Storage::disk('public')->delete($produit->image_principale);
            }
            $validated['image_principale'] = $request->file('image_principale')
                ->store('produits', 'public');
        }

        // Upload nouvelles images secondaires si fournies
        if ($request->hasFile('images_secondaires')) {
            if ($produit->images_secondaires) {
                foreach ($produit->images_secondaires as $image) {
                    \Storage::disk('public')->delete($image);
                }
            }

            $imagesSecondaires = [];
            foreach ($request->file('images_secondaires') as $image) {
                $imagesSecondaires[] = $image->store('produits', 'public');
            }
            $validated['images_secondaires'] = $imagesSecondaires;
        }

        // Mettre à jour le slug si le nom change
        if (isset($validated['nom']) && $validated['nom'] !== $produit->nom) {
            $validated['slug'] = $this->generateUniqueSlug($validated['nom'], $produit->id);
        }

        $produit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour',
            'data' => $produit,
        ]);
    }

    /**
     * Supprimer un produit
     */
    public function destroy(Request $request, $id)
    {
        $produit = $this->findProduitForManagement($request, $id);

        // Supprimer les images
        if ($produit->image_principale) {
            \Storage::disk('public')->delete($produit->image_principale);
        }
        if ($produit->images_secondaires) {
            foreach ($produit->images_secondaires as $image) {
                \Storage::disk('public')->delete($image);
            }
        }

        $produit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produit supprimé',
        ]);
    }

    /**
     * Mettre à jour le stock
     */
    public function updateStock(Request $request, $id)
    {
        $validated = $request->validate([
            'stock_disponible' => 'required|integer|min:0',
        ]);

        $produit = Produit::findOrFail($id);
        $produit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stock mis à jour',
            'data' => $produit,
        ]);
    }

    private function findProduitForManagement(Request $request, $id): Produit
    {
        return Produit::query()
            ->when($this->isLivreur($request), function ($query) use ($request) {
                $query->where('created_by_user_id', $request->user()->id);
            })
            ->findOrFail($id);
    }

    private function isLivreur(Request $request): bool
    {
        return $request->user()?->role === 'livreur';
    }

    protected function generateUniqueSlug(string $nom, ?int $ignoreId = null): string
    {
        $base = Str::slug($nom);
        $slug = $base;
        $suffix = 2;

        while (
            Produit::where('slug', $slug)
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
}
