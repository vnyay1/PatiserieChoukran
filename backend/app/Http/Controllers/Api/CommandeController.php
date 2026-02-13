<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\Panier;
use App\Models\User;
use App\Models\Notification;
use App\Models\HistoriqueStatutCommande;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CommandeController extends Controller
{
    private const TARIF_MIN = 1000;

    /**
     * Liste des commandes de l'utilisateur
     */
    public function index(Request $request)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        $query = Commande::where('user_id', $request->user()->id)
            ->with(['ligneCommandes.produit', 'adresseLivraison'])
            ->orderBy('created_at', 'desc');

        // Filtre par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $commandes = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $commandes,
        ]);
    }

    /**
     * Détail d'une commande
     */
    public function show(Request $request, $id)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        $commande = Commande::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with([
                'ligneCommandes.produit',
                'adresseLivraison',
                'livreur',
                'historiques.modifiePar'
            ])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $commande,
        ]);
    }

    /**
     * Créer une nouvelle commande
     */
    public function store(Request $request)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        $validated = $request->validate([
            'type_livraison' => 'required|in:livraison,retrait_boutique',
            'adresse_livraison_id' => 'exclude_unless:type_livraison,livraison|required|exists:adresses,id',
            'telephone_livraison' => 'exclude_unless:type_livraison,livraison|required|string',
            'date_livraison_souhaitee' => 'exclude_unless:type_livraison,livraison|required|date|after_or_equal:today',
            'heure_livraison_souhaitee' => 'exclude_unless:type_livraison,livraison|required|date_format:H:i|after_or_equal:09:00|before_or_equal:18:00',
            'instructions_speciales' => 'nullable|string|max:500',
            'moyen_paiement' => 'required|in:orange_money,mtn_momo,especes',
            'telephone_paiement' => 'required_unless:moyen_paiement,especes|string',
        ], [
            'heure_livraison_souhaitee.after_or_equal' => 'L\'heure de livraison doit être comprise entre 09:00 et 18:00.',
            'heure_livraison_souhaitee.before_or_equal' => 'L\'heure de livraison doit être comprise entre 09:00 et 18:00.',
        ]);

        Panier::purgerExpires($request->user()->id);

        // Vérifier que le panier n'est pas vide
        $panierItems = Panier::where('user_id', $request->user()->id)
            ->nonExpire()
            ->with('produit')
            ->get();

        if ($panierItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre panier est vide',
            ], 400);
        }

        $panierLivreur = $this->resolveLivreurForPanierItems($panierItems);
        if (!empty($panierLivreur['error'])) {
            return response()->json([
                'success' => false,
                'message' => $panierLivreur['error'],
            ], 422);
        }
        $livreur = $panierLivreur['livreur'];

        // Calculer les montants
        $montantProduits = $panierItems->sum('sous_total');
        
        // Calculer les frais de livraison
        $montantLivraison = 0;
        if ($validated['type_livraison'] === 'livraison') {
            $adresse = $request->user()->adresses()->findOrFail($validated['adresse_livraison_id']);
            $shipping = $this->calculateShippingForAdresse($adresse, $livreur?->id);

            if (!empty($shipping['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $shipping['error'],
                ], 422);
            }

            $montantLivraison = $shipping['frais_livraison'] ?? self::TARIF_MIN;
        }

        $montantTotal = $montantProduits + $montantLivraison;

        // Définir l'opérateur mobile
        $operateurMobile = null;
        if ($validated['moyen_paiement'] === 'orange_money') {
            $operateurMobile = 'orange';
        } elseif ($validated['moyen_paiement'] === 'mtn_momo') {
            $operateurMobile = 'mtn';
        }

        DB::beginTransaction();

        try {
            // Créer la commande
            $commande = Commande::create([
                'user_id' => $request->user()->id,
                'montant_produits' => $montantProduits,
                'montant_livraison' => $montantLivraison,
                'montant_total' => $montantTotal,
                'type_livraison' => $validated['type_livraison'],
                'adresse_livraison_id' => $validated['type_livraison'] === 'livraison'
                    ? ($validated['adresse_livraison_id'] ?? null)
                    : null,
                'telephone_livraison' => $validated['type_livraison'] === 'livraison'
                    ? ($validated['telephone_livraison'] ?? null)
                    : null,
                'date_livraison_souhaitee' => $validated['date_livraison_souhaitee'] ?? null,
                'heure_livraison_souhaitee' => $validated['heure_livraison_souhaitee'] ?? null,
                'instructions_speciales' => $validated['instructions_speciales'] ?? null,
                'moyen_paiement' => $validated['moyen_paiement'],
                'operateur_mobile' => $operateurMobile,
                'telephone_paiement' => $validated['telephone_paiement'] ?? null,
                'statut' => 'en_attente',
                'statut_paiement' => 'en_attente',
                'livreur_id' => $livreur?->id,
            ]);

            // Créer les lignes de commande
            foreach ($panierItems as $item) {
                LigneCommande::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $item->produit_id,
                    'nom_produit' => $item->produit->nom,
                    'quantite' => $item->quantite,
                    'prix_unitaire' => $item->prix_unitaire_actuel,
                    'sous_total' => $item->sous_total,
                ]);

                // Décrémenter le stock (indicatif)
                $item->produit->diminuerStock($item->quantite);
            }

            // Vider le panier
            Panier::where('user_id', $request->user()->id)->delete();

            // Enregistrer dans l'historique
            HistoriqueStatutCommande::create([
                'commande_id' => $commande->id,
                'ancien_statut' => null,
                'nouveau_statut' => 'en_attente',
                'commentaire' => 'Commande créée',
                'modifie_par_user_id' => $request->user()->id,
            ]);

            DB::commit();

            // Notification livreur hors application (email + notification interne)
            if ($livreur) {
                try {
                    $this->notifyAssignedLivreur($commande, $livreur);
                } catch (\Throwable $e) {
                    Log::warning('Echec notification livreur après création commande', [
                        'commande_id' => $commande->id,
                        'livreur_id' => $livreur->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Charger les relations
            $commande->load(['ligneCommandes.produit', 'adresseLivraison']);

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => $commande,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Annuler une commande
     */
    public function cancel(Request $request, $id)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        $commande = Commande::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        // Vérifier si la commande peut être annulée
        if (!in_array($commande->statut, ['en_attente', 'confirmee'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut plus être annulée',
            ], 400);
        }

        $commande->changerStatut('annulee', $request->user()->id, 'Annulée par le client');

        // Remettre le stock
        foreach ($commande->ligneCommandes as $ligne) {
            $ligne->produit->augmenterStock($ligne->quantite);
        }

        return response()->json([
            'success' => true,
            'message' => 'Commande annulée',
            'data' => $commande,
        ]);
    }

    /**
     * Modifier une commande (uniquement si en attente)
     */
    public function update(Request $request, $id)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        $commande = Commande::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($commande->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut plus être modifiée',
            ], 400);
        }

        $validated = $request->validate([
            'type_livraison' => 'sometimes|in:livraison,retrait_boutique',
            'adresse_livraison_id' => 'nullable|exists:adresses,id',
            'telephone_livraison' => 'nullable|string',
            'date_livraison_souhaitee' => 'nullable|sometimes|date|after_or_equal:today',
            'heure_livraison_souhaitee' => 'nullable|sometimes|date_format:H:i|after_or_equal:09:00|before_or_equal:18:00',
            'instructions_speciales' => 'nullable|string|max:500',
            'moyen_paiement' => 'sometimes|in:orange_money,mtn_momo,especes',
            'telephone_paiement' => 'nullable|string',
        ], [
            'heure_livraison_souhaitee.after_or_equal' => 'L\'heure de livraison doit être comprise entre 09:00 et 18:00.',
            'heure_livraison_souhaitee.before_or_equal' => 'L\'heure de livraison doit être comprise entre 09:00 et 18:00.',
        ]);

        $typeLivraison = $validated['type_livraison'] ?? $commande->type_livraison;
        $adresseLivraisonId = $typeLivraison === 'livraison'
            ? ($validated['adresse_livraison_id'] ?? $commande->adresse_livraison_id)
            : null;

        if ($typeLivraison === 'livraison' && !$adresseLivraisonId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une adresse de livraison valide.',
            ], 422);
        }

        // Recalculer les frais de livraison si nécessaire
        $montantLivraison = 0;
        if ($typeLivraison === 'livraison') {
            $adresse = $request->user()->adresses()->findOrFail($adresseLivraisonId);
            $shipping = $this->calculateShippingForAdresse($adresse, (int) $commande->livreur_id);

            if (!empty($shipping['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $shipping['error'],
                ], 422);
            }

            $montantLivraison = $shipping['frais_livraison'] ?? self::TARIF_MIN;
        }

        $moyenPaiement = $validated['moyen_paiement'] ?? $commande->moyen_paiement;
        $telephonePaiement = array_key_exists('telephone_paiement', $validated)
            ? $validated['telephone_paiement']
            : $commande->telephone_paiement;

        $telephoneLivraison = $typeLivraison === 'livraison'
            ? ($validated['telephone_livraison'] ?? $commande->telephone_livraison)
            : null;

        if ($moyenPaiement !== 'especes' && empty($telephonePaiement)) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez renseigner un numéro de téléphone pour le paiement.',
            ], 422);
        }

        $operateurMobile = null;
        if ($moyenPaiement === 'orange_money') {
            $operateurMobile = 'orange';
        } elseif ($moyenPaiement === 'mtn_momo') {
            $operateurMobile = 'mtn';
        }

        if ($moyenPaiement === 'especes') {
            $telephonePaiement = null;
        }

        $commande->update([
            'type_livraison' => $typeLivraison,
            'adresse_livraison_id' => $adresseLivraisonId,
            'telephone_livraison' => $telephoneLivraison,
            'date_livraison_souhaitee' => $validated['date_livraison_souhaitee'] ?? $commande->date_livraison_souhaitee,
            'heure_livraison_souhaitee' => $validated['heure_livraison_souhaitee'] ?? $commande->heure_livraison_souhaitee,
            'instructions_speciales' => $validated['instructions_speciales'] ?? $commande->instructions_speciales,
            'moyen_paiement' => $moyenPaiement,
            'operateur_mobile' => $operateurMobile,
            'telephone_paiement' => $telephonePaiement,
            'montant_livraison' => $montantLivraison,
            'montant_total' => $commande->montant_produits + $montantLivraison,
        ]);

        $commande->load(['ligneCommandes.produit', 'adresseLivraison']);

        return response()->json([
            'success' => true,
            'message' => 'Commande mise à jour',
            'data' => $commande,
        ]);
    }

    /**
     * Statistiques des commandes de l'utilisateur
     */
    public function stats(Request $request)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        $userId = $request->user()->id;

        $stats = [
            'total_commandes' => Commande::where('user_id', $userId)->count(),
            'en_cours' => Commande::where('user_id', $userId)
                ->whereIn('statut', ['en_attente', 'confirmee', 'en_preparation', 'prete', 'en_livraison'])
                ->count(),
            'livrees' => Commande::where('user_id', $userId)->livree()->count(),
            'annulees' => Commande::where('user_id', $userId)->where('statut', 'annulee')->count(),
            'montant_total_depense' => Commande::where('user_id', $userId)
                ->livree()
                ->sum('montant_total'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Calculer les frais de livraison pour une adresse
     */
    public function calculateShipping(Request $request)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        $validated = $request->validate([
            'adresse_id' => 'required|exists:adresses,id',
        ]);

        $adresse = $request->user()->adresses()->findOrFail($validated['adresse_id']);
        $panierLivreur = $this->resolvePanierLivreurId((int) $request->user()->id);

        if (!empty($panierLivreur['error'])) {
            return response()->json([
                'success' => false,
                'message' => $panierLivreur['error'],
            ], 422);
        }

        $shipping = $this->calculateShippingForAdresse($adresse, (int) $panierLivreur['livreur_id']);
        if (!empty($shipping['error'])) {
            return response()->json([
                'success' => false,
                'message' => $shipping['error'],
            ], 422);
        }

        $frais = $shipping['frais_livraison'] ?? self::TARIF_MIN;

        return response()->json([
            'success' => true,
            'data' => [
                'frais_livraison' => $frais,
                'zone' => $shipping['zone'],
            ]
        ]);
    }

    private function calculateShippingForAdresse($adresse, ?int $livreurId = null): array
    {
        if (!$adresse || !$adresse->zone_livraison_id) {
            return [
                'frais_livraison' => null,
                'zone' => null,
                'error' => 'Veuillez sélectionner une zone de livraison pour cette adresse.',
            ];
        }

        $zone = \App\Models\ZoneLivraison::find($adresse->zone_livraison_id);

        if (!$zone) {
            return [
                'frais_livraison' => null,
                'zone' => null,
                'error' => 'Zone de livraison invalide. Merci de sélectionner une zone valide.',
            ];
        }

        if (!$zone->est_active) {
            return [
                'frais_livraison' => null,
                'zone' => null,
                'error' => 'Cette zone de livraison est actuellement inactive.',
            ];
        }

        if ($livreurId !== null && (int) $zone->created_by_user_id !== (int) $livreurId) {
            return [
                'frais_livraison' => null,
                'zone' => null,
                'error' => 'Cette zone de livraison n\'est pas disponible pour les produits de votre panier.',
            ];
        }

        return [
            'frais_livraison' => (float) $zone->tarif_livraison,
            'zone' => [
                'id' => $zone->id,
                'nom_zone' => $zone->nom_zone,
                'ville' => $zone->ville,
                'tarif_livraison' => (float) $zone->tarif_livraison,
            ],
        ];
    }

    private function resolvePanierLivreurId(int $userId): array
    {
        Panier::purgerExpires($userId);

        $panierItems = Panier::where('user_id', $userId)
            ->nonExpire()
            ->with(['produit:id,created_by_user_id'])
            ->get();

        $panierLivreur = $this->resolveLivreurForPanierItems($panierItems);

        return [
            'livreur_id' => $panierLivreur['livreur']?->id,
            'error' => $panierLivreur['error'],
        ];
    }

    private function resolveLivreurForPanierItems(Collection $panierItems): array
    {
        if ($panierItems->isEmpty()) {
            return [
                'livreur' => null,
                'error' => 'Votre panier est vide.',
            ];
        }

        $creatorIds = $panierItems
            ->pluck('produit.created_by_user_id')
            ->filter()
            ->unique()
            ->values();

        if ($creatorIds->count() > 1) {
            return [
                'livreur' => null,
                'error' => 'Votre panier doit contenir uniquement des produits d\'un même livreur.',
            ];
        }

        if ($creatorIds->isEmpty()) {
            return [
                'livreur' => null,
                'error' => null,
            ];
        }

        $livreurId = (int) $creatorIds->first();
        $allItemsOwnedByCreator = $panierItems->every(function ($item) use ($livreurId) {
            return (int) ($item->produit->created_by_user_id ?? 0) === $livreurId;
        });

        if (!$allItemsOwnedByCreator) {
            return [
                'livreur' => null,
                'error' => 'Votre panier contient des produits non attribués à ce livreur.',
            ];
        }

        $livreur = User::where('id', $livreurId)
            ->where('role', 'livreur')
            ->first();

        if (!$livreur) {
            return [
                'livreur' => null,
                'error' => 'Aucun livreur valide n\'est associé aux produits sélectionnés.',
            ];
        }

        return [
            'livreur' => $livreur,
            'error' => null,
        ];
    }

    private function rejectAdmin(Request $request)
    {
        if ($request->user() && $request->user()->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Les administrateurs ne peuvent pas passer de commande.',
            ], 403);
        }

        return null;
    }

    private function notifyAssignedLivreur(Commande $commande, User $livreur): void
    {
        $title = 'Nouvelle commande assignée';
        $message = "La commande {$commande->numero_commande} vous a été assignée.";
        $actionUrl = '/admin/commandes';

        // Notification consultable dans l'app (historique)
        Notification::create([
            'user_id' => $livreur->id,
            'titre' => $title,
            'message' => $message,
            'type' => 'commande',
            'canal' => 'app',
            'est_lu' => false,
            'url_action' => $actionUrl,
            'date_envoi' => now(),
        ]);

        // Notification hors app: email
        if (empty($livreur->email)) {
            return;
        }

        try {
            Mail::raw(
                "{$message}\n\nMontant total: {$commande->montant_total} {$commande->devise}\nDate: {$commande->created_at?->format('d/m/Y H:i')}\n",
                function ($mail) use ($livreur, $commande, $title) {
                    $mail->to($livreur->email, $livreur->nom_complet)
                        ->subject("{$title} - {$commande->numero_commande}");
                }
            );

            Notification::create([
                'user_id' => $livreur->id,
                'titre' => $title,
                'message' => "Email envoyé au livreur pour {$commande->numero_commande}.",
                'type' => 'commande',
                'canal' => 'email',
                'est_lu' => false,
                'url_action' => $actionUrl,
                'date_envoi' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Echec envoi notification email livreur', [
                'commande_id' => $commande->id,
                'livreur_id' => $livreur->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
