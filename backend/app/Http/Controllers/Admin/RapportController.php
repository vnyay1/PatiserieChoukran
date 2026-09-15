<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RapportMensuelVendeurs;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Rapports mensuels des vendeurs (admin uniquement).
 */
class RapportController extends Controller
{
    private const MOIS_LISTES = 12;

    /**
     * Les 12 derniers mois (mois en cours compris) et les fichiers déjà générés.
     */
    public function index()
    {
        $disque = Storage::disk(RapportMensuelVendeurs::DISQUE);
        $courant = CarbonImmutable::now()->startOfMonth();

        $mois = collect(range(0, self::MOIS_LISTES - 1))->map(function (int $decalage) use ($courant, $disque) {
            $mois = $courant->subMonthsNoOverflow($decalage);

            return [
                'mois' => $mois->format('Y-m'),
                'libelle' => RapportMensuelVendeurs::libelle($mois),
                'clos' => RapportMensuelVendeurs::estClos($mois),
                'fichiers' => collect(RapportMensuelVendeurs::FORMATS)
                    ->mapWithKeys(fn (string $format) => [
                        $format => $disque->exists(RapportMensuelVendeurs::chemin($mois, $format)),
                    ]),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mois,
        ]);
    }

    /**
     * Aperçu JSON ou téléchargement PDF / CSV d'un mois.
     * Mois clos : fichier stocké resservi (généré au premier appel) ; mois en cours : à la volée.
     */
    public function mensuel(Request $request)
    {
        $validated = $request->validate([
            'mois' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'format' => 'nullable|in:json,pdf,csv',
        ], [
            'mois.regex' => 'Le mois doit être au format AAAA-MM.',
        ]);

        $mois = RapportMensuelVendeurs::mois($validated['mois']);
        if ($mois->greaterThan(CarbonImmutable::now()->startOfMonth())) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de générer le rapport d\'un mois qui n\'a pas commencé.',
            ], 422);
        }

        $format = $validated['format'] ?? 'json';

        if ($format === 'json') {
            return response()->json([
                'success' => true,
                'data' => RapportMensuelVendeurs::donnees($mois),
            ]);
        }

        $disque = Storage::disk(RapportMensuelVendeurs::DISQUE);
        $chemin = RapportMensuelVendeurs::chemin($mois, $format);

        if (RapportMensuelVendeurs::estClos($mois)) {
            if (! $disque->exists($chemin)) {
                RapportMensuelVendeurs::stocker($mois);
            }
            $contenu = $disque->get($chemin);
        } else {
            $contenu = RapportMensuelVendeurs::contenu($mois, $format);
        }

        return response($contenu, 200, [
            'Content-Type' => $format === 'pdf' ? 'application/pdf' : 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.basename($chemin).'"',
        ]);
    }
}
