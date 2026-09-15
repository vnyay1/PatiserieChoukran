<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Services\Factures;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Téléchargement de la facture PDF d'une commande, pour chaque rôle dans son périmètre.
 * 404 tant que la commande n'a pas été confirmée (pas encore de facture).
 */
class FactureController extends Controller
{
    public function client(Request $request, int $id): BinaryFileResponse
    {
        return $this->telecharger(
            Commande::where('user_id', $request->user()->id)->findOrFail($id)
        );
    }

    // Archives comprises : un vendeur peut retrouver la facture d'une commande terminée
    public function vendeur(Request $request, int $id): BinaryFileResponse
    {
        return $this->telecharger(
            Commande::where('vendeur_id', $request->user()->id)->findOrFail($id)
        );
    }

    public function admin(int $id): BinaryFileResponse
    {
        return $this->telecharger(Commande::findOrFail($id));
    }

    private function telecharger(Commande $commande): BinaryFileResponse
    {
        $facture = $commande->facture()->firstOrFail();

        return response()->download(
            Factures::cheminAssure($facture),
            $facture->nomFichier(),
            ['Content-Type' => 'application/pdf']
        );
    }
}
