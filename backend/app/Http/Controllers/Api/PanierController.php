<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Panier;
use App\Models\Produit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PanierController extends Controller
{
    /**
     * Afficher le panier de l'utilisateur
     */
    public function index(Request $request)
    {
        $panier = Panier::where('user_id', $request->user()->id)
            ->nonExpire()
            ->with('produit.categorie')
            ->get();

        $total = $panier->sum('sous_total');

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $panier,
                'total' => $total,
                'nombre_items' => $panier->count(),
            ]
        ]);
    }

    /**
     * Ajouter un produit au panier
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'produit_id' => 'required|exists:produits,id',
            'quantite' => 'required|integer|min:1',
        ]);

        $produit = Produit::findOrFail($validated['produit_id']);

        // Vérifier la disponibilité
        if (!$produit->est_disponible) {
            return response()->json([
                'success' => false,
                'message' => 'Ce produit n\'est pas disponible',
            ], 400);
        }

        // Vérifier le stock (indicatif)
        if ($produit->stock_disponible < $validated['quantite']) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant',
            ], 400);
        }

        // Vérifier si le produit existe déjà dans le panier
        $panierItem = Panier::where('user_id', $request->user()->id)
            ->where('produit_id', $validated['produit_id'])
            ->nonExpire()
            ->first();

        if ($panierItem) {
            // Mettre à jour la quantité
            $panierItem->quantite += $validated['quantite'];
            $panierItem->calculerSousTotal();
            $panierItem->date_expiration = Carbon::now()->addHours(24);
            $panierItem->save();
        } else {
            // Créer une nouvelle ligne
            $panierItem = Panier::create([
                'user_id' => $request->user()->id,
                'produit_id' => $validated['produit_id'],
                'quantite' => $validated['quantite'],
                'prix_unitaire_actuel' => $produit->prix_actuel,
                'sous_total' => $produit->prix_actuel * $validated['quantite'],
                'date_expiration' => Carbon::now()->addHours(24),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Produit ajouté au panier',
            'data' => $panierItem->load('produit'),
        ], 201);
    }

    /**
     * Mettre à jour la quantité d'un article du panier
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'quantite' => 'required|integer|min:1',
        ]);

        $panierItem = Panier::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        // Vérifier le stock
        if ($panierItem->produit->stock_disponible < $validated['quantite']) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant',
            ], 400);
        }

        $panierItem->quantite = $validated['quantite'];
        $panierItem->calculerSousTotal();
        $panierItem->date_expiration = Carbon::now()->addHours(24);
        $panierItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Panier mis à jour',
            'data' => $panierItem->load('produit'),
        ]);
    }

    /**
     * Supprimer un article du panier
     */
    public function destroy(Request $request, $id)
    {
        $panierItem = Panier::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $panierItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article retiré du panier',
        ]);
    }

    /**
     * Vider tout le panier
     */
    public function clear(Request $request)
    {
        Panier::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé',
        ]);
    }

    /**
     * Nombre d'articles dans le panier (pour badge)
     */
    public function count(Request $request)
    {
        $count = Panier::where('user_id', $request->user()->id)
            ->nonExpire()
            ->sum('quantite');

        return response()->json([
            'success' => true,
            'data' => ['count' => $count]
        ]);
    }
}

