<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Panier;
use App\Models\Produit;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Panier du client. Chaque endpoint vide d'abord le panier resté trop longtemps sans
 * modification (durée réglée par l'admin) et les actions renvoient le panier complet :
 * le SPA n'a pas besoin d'un second appel pour se mettre à jour.
 */
class PanierController extends Controller
{
    /**
     * Afficher le panier de l'utilisateur
     */
    public function index(Request $request)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        $this->purgeExpiredPanier($request);

        return $this->reponsePanier($request);
    }

    /**
     * Ajouter un produit au panier
     */
    public function store(Request $request)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        $this->purgeExpiredPanier($request);

        $validated = $request->validate([
            'produit_id' => 'required|integer|exists:produits,id',
            'quantite' => 'required|integer|min:1',
        ]);

        $produit = Produit::findOrFail($validated['produit_id']);

        // Disponible et en vente sur la boutique (catégorie active, vendeur actif au profil complet)
        if (! Produit::visible()->whereKey($produit->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce produit n\'est pas disponible',
            ], 400);
        }

        try {
            $refus = $this->ajouterAuPanier($request->user()->id, $produit, $validated['quantite']);
        } catch (UniqueConstraintViolationException) {
            // Deux ajouts simultanés du même produit (double clic) : l'autre requête vient
            // de créer la ligne (une seule par produit), celle-ci la complète
            $refus = $this->ajouterAuPanier($request->user()->id, $produit, $validated['quantite']);
        }

        if ($refus) {
            return response()->json(['success' => false, 'message' => $refus], 400);
        }

        Panier::prolonger($request->user()->id);

        return $this->reponsePanier($request, 'Produit ajouté au panier', 201);
    }

    /**
     * Ajoute la quantité à la ligne du produit (créée au besoin). Renvoie le motif du
     * refus quand le stock ne couvre pas la quantité déjà au panier + celle ajoutée.
     */
    private function ajouterAuPanier(int $userId, Produit $produit, int $quantite): ?string
    {
        $ligne = Panier::where('user_id', $userId)->where('produit_id', $produit->id)->first();

        $dejaAuPanier = $ligne?->quantite ?? 0;
        if ($produit->stock_disponible < $dejaAuPanier + $quantite) {
            $reste = max(0, $produit->stock_disponible - $dejaAuPanier);

            return $reste > 0
                ? "Stock insuffisant : vous pouvez encore ajouter {$reste} unité(s) de ce produit."
                : ($dejaAuPanier > 0
                    ? 'Tout le stock disponible de ce produit est déjà dans votre panier.'
                    : 'Ce produit est en rupture de stock.');
        }

        if ($ligne) {
            // Incrément atomique (aucune unité perdue entre deux ajouts simultanés), prix actualisé
            Panier::whereKey($ligne->id)->increment('quantite', $quantite, [
                'prix_unitaire_actuel' => $produit->prix_actuel,
                'vendeur_id' => $produit->created_by_user_id,
            ]);
            $ligne->refresh()->calculerSousTotal();

            return null;
        }

        Panier::create([
            'user_id' => $userId,
            'produit_id' => $produit->id,
            'vendeur_id' => $produit->created_by_user_id,
            'quantite' => $quantite,
            'prix_unitaire_actuel' => $produit->prix_actuel,
            'sous_total' => $produit->prix_actuel * $quantite,
        ]);

        return null;
    }

    /**
     * Mettre à jour la quantité d'un article du panier
     */
    public function update(Request $request, $id)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        $this->purgeExpiredPanier($request);

        $validated = $request->validate([
            'quantite' => 'required|integer|min:1',
        ]);

        $panierItem = Panier::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with('produit')
            ->firstOrFail();

        // Vérifier le stock
        if ($panierItem->produit->stock_disponible < $validated['quantite']) {
            return response()->json([
                'success' => false,
                'message' => "Stock insuffisant : {$panierItem->produit->stock_disponible} unité(s) disponible(s).",
            ], 400);
        }

        $panierItem->quantite = $validated['quantite'];
        $panierItem->vendeur_id = $panierItem->produit->created_by_user_id;
        $panierItem->calculerSousTotal();
        Panier::prolonger($request->user()->id);

        return $this->reponsePanier($request, 'Panier mis à jour');
    }

    /**
     * Supprimer un article du panier
     */
    public function destroy(Request $request, $id)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        $this->purgeExpiredPanier($request);

        Panier::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail()
            ->delete();

        Panier::prolonger($request->user()->id);

        return $this->reponsePanier($request, 'Article retiré du panier');
    }

    /**
     * Vider tout le panier
     */
    public function clear(Request $request)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        Panier::where('user_id', $request->user()->id)->delete();

        return $this->reponsePanier($request, 'Panier vidé');
    }

    /**
     * Panier complet : lignes, total et heure à laquelle il sera vidé sans nouvelle modification.
     */
    private function reponsePanier(Request $request, ?string $message = null, int $statut = 200): JsonResponse
    {
        $userId = $request->user()->id;

        $panier = Panier::where('user_id', $userId)
            ->with(['produit.categorie', 'produit.createur:id,nom_complet', 'vendeur:id,nom_complet'])
            ->orderBy('id')
            ->get();

        $panier->each(function (Panier $panierItem) {
            $this->syncVendeurFromProduit($panierItem);
            $this->syncPrixFromProduit($panierItem);
        });

        return response()->json(array_filter([
            'success' => true,
            'message' => $message,
            'data' => [
                'items' => $panier,
                'total' => $panier->sum('sous_total'),
                'nombre_items' => $panier->count(),
                'expire_le' => Panier::expireLe($userId)?->toIso8601String(),
                'duree_minutes' => Panier::dureeMinutes(),
            ],
        ], fn ($valeur) => $valeur !== null), $statut);
    }

    private function purgeExpiredPanier(Request $request): void
    {
        if ($request->user()) {
            Panier::purgerExpires($request->user()->id);
        }
    }

    private function rejectAdmin(Request $request)
    {
        if ($request->user() && $request->user()->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Les administrateurs ne peuvent pas utiliser le panier.',
            ], 403);
        }

        return null;
    }

    // Les corrections de vendeur et de prix ne prolongent pas le panier (updated_at conservé)
    private function syncVendeurFromProduit(Panier $panierItem): void
    {
        $vendeurId = $panierItem->produit?->created_by_user_id;

        if ((int) $panierItem->vendeur_id !== (int) $vendeurId) {
            $panierItem->vendeur_id = $vendeurId;
            $panierItem->timestamps = false;
            $panierItem->save();
            $panierItem->timestamps = true;
            $panierItem->load('vendeur:id,nom_complet');
        }
    }

    // Le checkout facture au prix actuel du produit : le panier affiche donc le même prix
    private function syncPrixFromProduit(Panier $panierItem): void
    {
        $prixActuel = $panierItem->produit?->prix_actuel;

        if ($prixActuel !== null && (float) $panierItem->prix_unitaire_actuel !== (float) $prixActuel) {
            $panierItem->prix_unitaire_actuel = $prixActuel;
            $panierItem->sous_total = $prixActuel * $panierItem->quantite;
            $panierItem->timestamps = false;
            $panierItem->save();
            $panierItem->timestamps = true;
        }
    }
}
