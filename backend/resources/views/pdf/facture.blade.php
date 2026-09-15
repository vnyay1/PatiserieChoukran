@php
    $fcfa = fn ($montant) => number_format((float) $montant, 0, ',', ' ').' FCFA';
    $moyens = ['orange_money' => 'Orange Money', 'mtn_momo' => 'MTN Mobile Money', 'especes' => 'Espèces'];
    $adresse = $commande->adresseLivraison;
    $paye = $commande->statut_paiement === 'paye';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $facture->numero_facture }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #2d2a26; }
        table { width: 100%; border-collapse: collapse; }
        .entete td { vertical-align: top; }
        .logo { width: 78px; height: 78px; }
        .boutique { font-size: 17px; font-weight: bold; color: #975a16; margin-bottom: 4px; }
        .gris { color: #6b665f; }
        .titre { text-align: right; }
        .titre h1 { margin: 0; font-size: 26px; letter-spacing: 2px; color: #975a16; }
        .badge { display: inline-block; padding: 3px 9px; border-radius: 9px; font-size: 10px; font-weight: bold; }
        .badge-paye { background: #c6f6d5; color: #22543d; }
        .badge-attente { background: #feebc8; color: #7b341e; }
        .bloc { margin-top: 22px; }
        .bloc td { width: 50%; vertical-align: top; padding: 10px 12px; background: #fbf6ee; border: 1px solid #efe3cf; }
        .etiquette { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #975a16; font-weight: bold; margin-bottom: 5px; }
        .lignes { margin-top: 22px; }
        .lignes th { background: #975a16; color: #fff; padding: 7px 8px; text-align: left; font-size: 10px; }
        .lignes td { padding: 7px 8px; border-bottom: 1px solid #efe3cf; }
        .droite { text-align: right; }
        .totaux { margin-top: 12px; width: 45%; margin-left: 55%; }
        .totaux td { padding: 5px 8px; }
        .total td { border-top: 2px solid #975a16; font-size: 14px; font-weight: bold; color: #975a16; padding-top: 8px; }
        .pied { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 9px; color: #6b665f; border-top: 1px solid #efe3cf; padding-top: 6px; }
    </style>
</head>
<body>
    <table class="entete">
        <tr>
            @if ($logo)
                <td style="width: 90px;">
                    <img src="{{ $logo }}" class="logo" alt="Logo">
                </td>
            @endif
            <td>
                <div class="boutique">{{ $vendeur?->nom_complet ?? 'Vendeur' }}</div>
                @if ($vendeur?->email)<div class="gris">{{ $vendeur->email }}</div>@endif
                @if ($vendeur?->telephone)<div class="gris">{{ $vendeur->telephone }}</div>@endif
                <div class="gris">Vendu sur {{ config('app.name') }}</div>
            </td>
            <td class="titre">
                <h1>FACTURE</h1>
                <div><strong>{{ $facture->numero_facture }}</strong></div>
                <div class="gris">Émise le {{ $facture->created_at->format('d/m/Y') }}</div>
                <div class="gris">Commande {{ $commande->numero_commande }} du {{ $commande->created_at->format('d/m/Y') }}</div>
                <div style="margin-top: 6px;">
                    @if ($paye)
                        <span class="badge badge-paye">PAYÉE{{ $commande->date_paiement ? ' LE '.$commande->date_paiement->format('d/m/Y') : '' }}</span>
                    @else
                        <span class="badge badge-attente">À RÉGLER</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="bloc">
        <tr>
            <td>
                <div class="etiquette">Facturé à</div>
                <strong>{{ $client?->nom_complet }}</strong><br>
                <span class="gris">{{ $client?->telephone }}</span>
                @if ($client?->email)<br><span class="gris">{{ $client->email }}</span>@endif
            </td>
            <td>
                @if ($commande->type_livraison === 'livraison')
                    <div class="etiquette">Livraison à domicile</div>
                    @if ($adresse)
                        {{ $adresse->quartier }}, {{ \Illuminate\Support\Str::title($adresse->ville) }}<br>
                        @if ($adresse->point_repere)<span class="gris">Repère : {{ $adresse->point_repere }}</span><br>@endif
                        @if ($adresse->complement_adresse)<span class="gris">{{ $adresse->complement_adresse }}</span><br>@endif
                    @endif
                    <span class="gris">Contact : {{ $commande->telephone_livraison ?? $adresse?->telephone_contact }}</span>
                    @if ($commande->date_livraison_souhaitee)
                        <br><span class="gris">Souhaitée le {{ $commande->date_livraison_souhaitee->format('d/m/Y') }}{{ $commande->heure_livraison_souhaitee ? ' à '.substr($commande->heure_livraison_souhaitee, 0, 5) : '' }}</span>
                    @endif
                @else
                    <div class="etiquette">Retrait en boutique</div>
                    À récupérer auprès du vendeur.
                @endif
            </td>
        </tr>
    </table>

    <table class="lignes">
        <thead>
            <tr>
                <th>Produit</th>
                <th class="droite" style="width: 60px;">Qté</th>
                <th class="droite" style="width: 110px;">Prix unitaire</th>
                <th class="droite" style="width: 110px;">Sous-total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($commande->ligneCommandes as $ligne)
                <tr>
                    <td>{{ $ligne->nom_produit }}</td>
                    <td class="droite">{{ $ligne->quantite }}</td>
                    <td class="droite">{{ $fcfa($ligne->prix_unitaire) }}</td>
                    <td class="droite">{{ $fcfa($ligne->sous_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totaux">
        <tr>
            <td>Produits</td>
            <td class="droite">{{ $fcfa($commande->montant_produits) }}</td>
        </tr>
        <tr>
            <td>Livraison</td>
            <td class="droite">{{ (float) $commande->montant_livraison > 0 ? $fcfa($commande->montant_livraison) : 'Aucun frais' }}</td>
        </tr>
        <tr class="total">
            <td>Total</td>
            <td class="droite">{{ $fcfa($commande->montant_total) }}</td>
        </tr>
    </table>

    <table class="bloc">
        <tr>
            <td>
                <div class="etiquette">Paiement</div>
                {{ $moyens[$commande->moyen_paiement] ?? $commande->moyen_paiement }}
                @if ($commande->telephone_paiement)<span class="gris"> ({{ $commande->telephone_paiement }})</span>@endif
                @if ($commande->reference_paiement)<br><span class="gris">Référence : {{ $commande->reference_paiement }}</span>@endif
            </td>
            <td>
                <div class="etiquette">Statut</div>
                @if ($paye)
                    Réglée{{ $commande->date_paiement ? ' le '.$commande->date_paiement->format('d/m/Y') : '' }}.
                @elseif ($commande->moyen_paiement === 'especes')
                    À régler en espèces {{ $commande->type_livraison === 'livraison' ? 'à la livraison' : 'au retrait' }}.
                @else
                    En attente de réception du paiement mobile.
                @endif
            </td>
        </tr>
    </table>

    <div class="pied">
        {{ config('app.name') }} · Montants en francs CFA (XAF) · Facture générée automatiquement à la confirmation de la commande
    </div>
</body>
</html>
