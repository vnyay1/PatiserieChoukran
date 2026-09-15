<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\HistoriqueStatutCommande;
use App\Models\LigneCommande;
use App\Models\Panier;
use App\Models\Produit;
use App\Models\User;
use App\Services\LivraisonVendeur;
use App\Services\NotificationsCommande;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommandeController extends Controller
{
    /**
     * Liste des commandes de l'utilisateur
     */
    public function index(Request $request)
    {
        if ($response = $this->rejectAdmin($request)) {
            return $response;
        }

        $query = Commande::where('user_id', $request->user()->id)
            ->with(['ligneCommandes.produit', 'adresseLivraison', 'vendeur:id,nom_complet,telephone'])
            ->orderBy('created_at', 'desc');

        // Filtre par statut ("en_cours" = toutes les commandes non terminées)
        if ($request->filled('statut')) {
            if ($request->statut === 'en_cours') {
                $query->whereIn('statut', Commande::STATUTS_EN_COURS);
            } else {
                $query->where('statut', $request->statut);
            }
        }

        $commandes = $query->paginate(min(max((int) $request->get('per_page', 10), 1), 50));

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
                'vendeur:id,nom_complet,telephone',
                'historiques.modifiePar:id,nom_complet,role',
                'facture:id,commande_id,numero_facture,envoyee_le',
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

        if ($validated['type_livraison'] === 'livraison') {
            $erreurCreneau = $this->verifierCreneauLivraison(
                $validated['date_livraison_souhaitee'],
                $validated['heure_livraison_souhaitee']
            );
            if ($erreurCreneau) {
                return response()->json([
                    'success' => false,
                    'message' => $erreurCreneau,
                ], 422);
            }
        }

        Panier::purgerExpires($request->user()->id);

        $panierItems = Panier::where('user_id', $request->user()->id)
            ->with(['produit.createur:id,nom_complet', 'vendeur:id,nom_complet'])
            ->get();

        if ($panierItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre panier est vide',
            ], 400);
        }

        $this->syncPanierVendeurIds($panierItems);

        $adresse = null;
        if ($validated['type_livraison'] === 'livraison') {
            $adresse = $request->user()
                ->adresses()
                ->with('quartierLivraison')
                ->findOrFail($validated['adresse_livraison_id']);

            if (! $adresse->quartier_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez sélectionner un quartier pour cette adresse.',
                ], 422);
            }
        }

        $operateurMobile = null;
        if ($validated['moyen_paiement'] === 'orange_money') {
            $operateurMobile = 'orange';
        } elseif ($validated['moyen_paiement'] === 'mtn_momo') {
            $operateurMobile = 'mtn';
        }

        $commandes = [];
        $vendeursANotifier = [];

        try {
            DB::transaction(function () use ($panierItems, $validated, $adresse, $operateurMobile, $request, &$commandes, &$vendeursANotifier) {
                // Verrou sur les produits : deux clients ne peuvent pas acheter la dernière unité
                // en même temps (le stock est relu et vérifié à l'intérieur de la transaction).
                $produits = Produit::whereIn('id', $panierItems->pluck('produit_id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $groupes = $panierItems->groupBy(fn (Panier $item) => $item->vendeur_id);

                foreach ($groupes as $vendeurId => $items) {
                    $vendeurId = $vendeurId !== '' ? (int) $vendeurId : null;
                    $vendeur = $vendeurId ? User::with('villesLivraison')->find($vendeurId) : null;

                    if (! $vendeurId) {
                        throw new \InvalidArgumentException('Certains produits du panier ne sont associés à aucun vendeur.');
                    }

                    if (! $vendeur || ! $vendeur->estVendeurEnActivite()) {
                        $nomVendeur = $vendeur?->nom_complet ?? "Vendeur #{$vendeurId}";
                        throw new \InvalidArgumentException(
                            "Le vendeur « {$nomVendeur} » n'accepte plus de commandes pour le moment : retirez ses produits du panier."
                        );
                    }

                    // Disponibilité, stock et prix relus au moment de la commande (pas ceux de l'ajout au panier)
                    $lignes = [];
                    foreach ($items as $item) {
                        $produit = $produits->get($item->produit_id);
                        $nomProduit = $produit?->nom ?? $item->produit?->nom ?? 'Un produit';

                        if (! $produit || ! $produit->est_disponible) {
                            throw new \InvalidArgumentException("« {$nomProduit} » n'est plus disponible : retirez-le de votre panier.");
                        }

                        if ($produit->stock_disponible < $item->quantite) {
                            throw new \InvalidArgumentException(
                                "Stock insuffisant pour « {$nomProduit} » : il en reste {$produit->stock_disponible}."
                            );
                        }

                        $lignes[] = [
                            'produit' => $produit,
                            'quantite' => $item->quantite,
                            'prix_unitaire' => (float) $produit->prix_actuel,
                        ];
                    }

                    $montantProduits = collect($lignes)->sum(fn ($ligne) => $ligne['prix_unitaire'] * $ligne['quantite']);
                    $montantLivraison = 0;

                    // Frais standard, ville livrée et minimum d'achat du vendeur
                    if ($validated['type_livraison'] === 'livraison') {
                        $montantLivraison = LivraisonVendeur::verifier($vendeur, $adresse->villeDeLivraison(), $montantProduits);
                    }

                    $commande = Commande::create([
                        'user_id' => $request->user()->id,
                        'vendeur_id' => $vendeurId,
                        'livreur_id' => $vendeurId,
                        'montant_produits' => $montantProduits,
                        'montant_livraison' => $montantLivraison,
                        'montant_total' => $montantProduits + $montantLivraison,
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
                    ]);

                    foreach ($lignes as $ligne) {
                        LigneCommande::create([
                            'commande_id' => $commande->id,
                            'produit_id' => $ligne['produit']->id,
                            'nom_produit' => $ligne['produit']->nom,
                            'quantite' => $ligne['quantite'],
                            'prix_unitaire' => $ligne['prix_unitaire'],
                            'sous_total' => $ligne['prix_unitaire'] * $ligne['quantite'],
                        ]);

                        $ligne['produit']->diminuerStock($ligne['quantite']);
                        // Alimente le tri « Populaires » et le top produits du tableau de bord
                        $ligne['produit']->incrementerCommandes();
                    }

                    HistoriqueStatutCommande::create([
                        'commande_id' => $commande->id,
                        'ancien_statut' => null,
                        'nouveau_statut' => 'en_attente',
                        'commentaire' => 'Commande créée',
                        'modifie_par_user_id' => $request->user()->id,
                    ]);

                    $commandes[] = $commande->load(['ligneCommandes.produit', 'adresseLivraison.quartierLivraison', 'vendeur:id,nom_complet,telephone']);

                    if ($vendeur) {
                        $vendeursANotifier[] = [$commande, $vendeur];
                    }
                }

                Panier::where('user_id', $request->user()->id)->delete();
            });

            // Après la transaction : chaque vendeur est prévenu de sa commande
            foreach ($vendeursANotifier as [$commande, $vendeur]) {
                NotificationsCommande::nouvelleCommande($commande, $vendeur);
            }

            return response()->json([
                'success' => true,
                'message' => count($commandes).' commande(s) créée(s) avec succès',
                'data' => $commandes,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur lors de la création des commandes multi-vendeur', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
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
        if (! in_array($commande->statut, ['en_attente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut plus être annulée',
            ], 400);
        }

        // changerStatut remet aussi le stock des produits (voir Commande::changerStatut)
        $commande->changerStatut('annulee', $request->user()->id, 'Annulée par le client');

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

        // Nouveau créneau demandé : il doit être dans le futur
        $dateActuelle = $commande->date_livraison_souhaitee?->format('Y-m-d');
        $heureActuelle = $commande->heure_livraison_souhaitee ? substr($commande->heure_livraison_souhaitee, 0, 5) : null;
        $nouvelleDate = $validated['date_livraison_souhaitee'] ?? $dateActuelle;
        $nouvelleHeure = $validated['heure_livraison_souhaitee'] ?? $heureActuelle;

        if ($typeLivraison === 'livraison' && ($nouvelleDate !== $dateActuelle || $nouvelleHeure !== $heureActuelle)) {
            $erreurCreneau = $this->verifierCreneauLivraison($nouvelleDate, $nouvelleHeure);
            if ($erreurCreneau) {
                return response()->json([
                    'success' => false,
                    'message' => $erreurCreneau,
                ], 422);
            }
        }

        $adresseLivraisonId = $typeLivraison === 'livraison'
            ? ($validated['adresse_livraison_id'] ?? $commande->adresse_livraison_id)
            : null;

        if ($typeLivraison === 'livraison' && ! $adresseLivraisonId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une adresse de livraison valide.',
            ], 422);
        }

        // Livraison revérifiée pour la nouvelle adresse : ville livrée et minimum du vendeur
        $montantLivraison = 0;
        if ($typeLivraison === 'livraison') {
            $adresse = $request->user()->adresses()->with('quartierLivraison')->findOrFail($adresseLivraisonId);
            $vendeur = User::with('villesLivraison')->find($commande->vendeur_id);

            if (! $adresse->quartier_id || ! $vendeur) {
                return response()->json([
                    'success' => false,
                    'message' => ! $vendeur
                        ? 'Le vendeur de cette commande est introuvable.'
                        : 'Veuillez sélectionner un quartier pour cette adresse.',
                ], 422);
            }

            try {
                $montantLivraison = LivraisonVendeur::verifier($vendeur, $adresse->villeDeLivraison(), (float) $commande->montant_produits);
            } catch (\InvalidArgumentException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
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

        $commande->load(['ligneCommandes.produit', 'adresseLivraison', 'vendeur:id,nom_complet,telephone', 'historiques.modifiePar:id,nom_complet,role']);

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
                ->whereIn('statut', Commande::STATUTS_EN_COURS)
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
     * Le créneau de livraison demandé doit être à venir (fuseau de l'application).
     * Renvoie un message d'erreur, ou null si le créneau est valide.
     */
    private function verifierCreneauLivraison(?string $date, ?string $heure): ?string
    {
        if (! $date || ! $heure) {
            return null;
        }

        try {
            $creneau = Carbon::parse($date)->setTimeFromTimeString(substr($heure, 0, 5));
        } catch (\Throwable) {
            return 'Date ou heure de livraison invalide.';
        }

        return $creneau->isPast()
            ? 'Ce créneau de livraison est déjà passé : choisissez une heure à venir.'
            : null;
    }

    private function syncPanierVendeurIds(Collection $panierItems): void
    {
        foreach ($panierItems as $item) {
            $vendeurId = $item->produit?->created_by_user_id;

            if ((int) $item->vendeur_id !== (int) $vendeurId) {
                $item->vendeur_id = $vendeurId;
                $item->save();
            }
        }
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
}
