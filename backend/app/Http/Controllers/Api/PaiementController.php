<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Paiement;
use App\Services\NotchPay;
use App\Services\Paiements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Paiement mobile money des commandes via NotchPay.
 */
class PaiementController extends Controller
{
    /**
     * (Re)lancer le paiement en ligne d'une commande non payée (client).
     */
    public function payer(Request $request, int $id): JsonResponse
    {
        $commande = Commande::where('user_id', $request->user()->id)->findOrFail($id);

        $refus = match (true) {
            ! in_array($commande->moyen_paiement, Paiements::MOYENS, true) => 'Cette commande se règle en espèces.',
            $commande->isPaid() => 'Cette commande est déjà payée.',
            $commande->isAnnulee() => 'Cette commande est annulée.',
            ! NotchPay::estConfigure() => 'Le paiement en ligne n\'est pas encore disponible : le vendeur confirmera votre paiement.',
            default => null,
        };

        if ($refus) {
            return response()->json(['success' => false, 'message' => $refus], 422);
        }

        try {
            ['paiement' => $paiement, 'url' => $url] = Paiements::demarrer(collect([$commande]), $request->user());
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }

        return response()->json([
            'success' => true,
            'data' => ['reference' => $paiement->reference, 'url_paiement' => $url],
        ]);
    }

    /**
     * Retour du client depuis NotchPay : statut revérifié auprès de l'API.
     */
    public function show(Request $request, string $reference): JsonResponse
    {
        // NotchPay peut renvoyer au retour sa propre référence plutôt que la nôtre
        $paiement = Paiement::where('user_id', $request->user()->id)
            ->where(fn ($query) => $query->where('reference', $reference)->orWhere('notchpay_id', $reference))
            ->firstOrFail();

        $paiement = Paiements::synchroniser($paiement);

        return response()->json([
            'success' => true,
            'data' => [
                'reference' => $paiement->reference,
                'statut' => $paiement->statut,
                'montant' => (float) $paiement->montant,
                'paye_le' => $paiement->paye_le,
                'commandes' => $paiement->commandes()->get(['id', 'numero_commande', 'montant_total', 'statut', 'statut_paiement']),
            ],
        ]);
    }

    /**
     * Webhook NotchPay (public) : signature vérifiée, puis statut relu auprès de l'API.
     */
    public function webhook(Request $request): JsonResponse
    {
        if (! NotchPay::signatureValide($request->getContent(), $request->header('X-Notch-Signature'))) {
            Log::warning('Webhook NotchPay refusé : signature invalide', ['ip' => $request->ip()]);

            return response()->json(['success' => false, 'message' => 'Signature invalide.'], 401);
        }

        // Selon la version de l'API, notre référence arrive dans reference ou merchant_reference
        // et l'identifiant NotchPay dans id ou reference : on cherche parmi toutes
        $candidats = collect(['data.merchant_reference', 'data.reference', 'data.trxref', 'data.id'])
            ->map(fn (string $cle) => $request->input($cle))
            ->filter(fn ($valeur) => is_string($valeur) && $valeur !== '')
            ->unique()
            ->values();

        $paiement = $candidats->isEmpty() ? null : Paiement::whereIn('reference', $candidats)
            ->orWhereIn('notchpay_id', $candidats)
            ->first();

        if ($paiement) {
            Paiements::synchroniser($paiement);
        }

        // Toujours 200 pour une signature valide : NotchPay ne renvoie pas un événement ignoré
        return response()->json(['success' => true]);
    }
}
