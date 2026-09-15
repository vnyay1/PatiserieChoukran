<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Produit;
use App\Models\User;
use App\Models\VendeurTarifLivraison;
use App\Services\NotificationsCompte;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filtre par role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filtre par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        // Vendeurs mis en vedette
        if ($request->boolean('vedette')) {
            $query->vendeurs()->where('est_vendeur_vedette', true);
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom_complet', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount('commandes')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Détail d'un utilisateur
     */
    public function show($id)
    {
        $user = User::with(['commandes', 'adresses'])
            ->withCount('commandes')
            ->findOrFail($id);

        // Statistiques du vendeur
        if ($user->role === 'vendeur') {
            $commandesRecues = Commande::where('vendeur_id', $user->id);

            $user->stats = [
                'produits' => Produit::where('created_by_user_id', $user->id)->count(),
                'commandes_recues' => (clone $commandesRecues)->count(),
                'commandes_en_cours' => (clone $commandesRecues)->whereIn('statut', Commande::STATUTS_EN_COURS)->count(),
                'chiffre_affaires' => (clone $commandesRecues)->where('statut_paiement', 'paye')->sum('montant_total'),
                'quartiers_desservis' => VendeurTarifLivraison::where('vendeur_id', $user->id)->where('actif', true)->count(),
            ];
        }

        // Statistiques du client
        if ($user->role === 'client') {
            $user->stats = [
                'total_depense' => $user->commandes()->where('statut_paiement', 'paye')->sum('montant_total'),
                'commandes_livrees' => $user->commandes()->livree()->count(),
                'commande_moyenne' => $user->commandes()->where('statut_paiement', 'paye')->avg('montant_total'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Changer le statut d'un utilisateur
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'statut' => 'required|in:actif,inactif,suspendu',
        ]);

        $user = User::findOrFail($id);

        if ($request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre statut.',
            ], 400);
        }

        $user->update($validated);

        // Compte suspendu/désactivé : déconnexion immédiate de tous ses appareils
        if ($user->statut !== 'actif') {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour',
            'data' => $user,
        ]);
    }

    /**
     * Changer le rôle d'un utilisateur
     */
    public function updateRole(Request $request, $id)
    {
        $validated = $request->validate([
            'role' => 'required|in:client,admin,vendeur',
        ]);

        $user = User::findOrFail($id);

        if ($request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas changer votre propre rôle.',
            ], 400);
        }

        $ancienRole = $user->role;
        $user->update($validated);

        // Nouveau vendeur : il doit compléter son profil boutique avant de vendre
        if ($user->isVendeur() && $ancienRole !== 'vendeur') {
            NotificationsCompte::devenuVendeur($user);
        }

        // Un vendeur rétrogradé perd sa mise en avant
        if (! $user->isVendeur() && $user->est_vendeur_vedette) {
            $user->update(['est_vendeur_vedette' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rôle mis à jour',
            'data' => $user,
        ]);
    }

    /**
     * Mettre un vendeur en vedette (ses produits passent en tête du catalogue) ou l'en retirer
     */
    public function updateVedette(Request $request, $id)
    {
        $validated = $request->validate([
            'est_vendeur_vedette' => 'required|boolean',
        ]);

        $user = User::findOrFail($id);

        if (! $user->isVendeur()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un vendeur peut être mis en vedette.',
            ], 422);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => $user->est_vendeur_vedette
                ? "« {$user->nom_complet} » est maintenant en vedette"
                : "« {$user->nom_complet} » n'est plus en vedette",
            'data' => $user,
        ]);
    }
}
