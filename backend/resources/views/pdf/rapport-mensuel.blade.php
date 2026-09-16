@php
    $montant = fn ($valeur) => \App\Services\RapportMensuelVendeurs::formaterMontant($valeur);
    $totaux = $rapport['totaux'];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport des vendeurs - {{ $rapport['libelle'] }}</title>
    <style>
        @page { margin: 26px 28px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #2d2a26; }
        h1 { margin: 0; font-size: 20px; color: #975a16; }
        .sous-titre { color: #6b665f; margin-top: 3px; }
        .avertissement { margin-top: 6px; color: #9c4221; }
        table { width: 100%; border-collapse: collapse; }
        .cartes { margin-top: 14px; }
        .cartes td { width: 20%; padding: 8px 10px; background: #fbf6ee; border: 1px solid #efe3cf; }
        .cartes .valeur { font-size: 14px; font-weight: bold; color: #975a16; }
        .cartes .libelle { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #6b665f; }
        .detail { margin-top: 16px; }
        .detail th { background: #975a16; color: #fff; padding: 6px 5px; font-size: 8.5px; text-align: right; }
        .detail th.gauche, .detail td.gauche { text-align: left; }
        .detail td { padding: 5px; border-bottom: 1px solid #efe3cf; text-align: right; }
        .detail tr.pair td { background: #fdfaf5; }
        .detail tr.total td { border-top: 2px solid #975a16; font-weight: bold; color: #975a16; background: #fbf6ee; }
        .vedette { color: #b7791f; }
        .gris { color: #6b665f; }
        .pied { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8px; color: #6b665f; text-align: center; }
    </style>
</head>
<body>
    <h1>Rapport mensuel des vendeurs</h1>
    <div class="sous-titre">
        {{ ucfirst($rapport['libelle']) }} · {{ config('app.name') }} · généré le {{ \Illuminate\Support\Carbon::parse($rapport['genere_le'])->format('d/m/Y à H:i') }}
    </div>
    @unless ($rapport['clos'])
        <div class="avertissement">Mois en cours : chiffres provisoires.</div>
    @endunless

    <table class="cartes">
        <tr>
            <td><div class="libelle">Commandes</div><div class="valeur">{{ $totaux['commandes'] }}</div></td>
            <td><div class="libelle">Livrées</div><div class="valeur">{{ $totaux['livrees'] }}</div></td>
            <td><div class="libelle">Chiffre d'affaires</div><div class="valeur">{{ $montant($totaux['chiffre_affaires']) }} FCFA</div></td>
            <td><div class="libelle">Encaissé</div><div class="valeur">{{ $montant($totaux['encaisse']) }} FCFA</div></td>
            <td><div class="libelle">Panier moyen</div><div class="valeur">{{ $montant($totaux['panier_moyen']) }} FCFA</div></td>
        </tr>
    </table>

    <table class="detail">
        <thead>
            <tr>
                <th class="gauche">Vendeur</th>
                <th>Cmd.</th>
                <th>Livrées</th>
                <th>Annulées</th>
                <th>En cours</th>
                <th>Articles</th>
                <th>Chiffre d'affaires</th>
                <th>Encaissé</th>
                <th>Livraison</th>
                <th>Panier moyen</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rapport['vendeurs'] as $index => $ligne)
                <tr class="{{ $index % 2 ? 'pair' : '' }}">
                    <td class="gauche">
                        <strong>{{ $ligne['nom_complet'] }}</strong>
                        @if ($ligne['vedette'] === 'Oui')<span class="vedette"> ★</span>@endif
                        <br><span class="gris">{{ $ligne['telephone'] }}{{ $ligne['email'] ? ' · '.$ligne['email'] : '' }}</span>
                    </td>
                    <td>{{ $ligne['commandes'] }}</td>
                    <td>{{ $ligne['livrees'] }}</td>
                    <td>{{ $ligne['annulees'] }}</td>
                    <td>{{ $ligne['en_cours'] }}</td>
                    <td>{{ $ligne['articles_vendus'] }}</td>
                    <td>{{ $montant($ligne['chiffre_affaires']) }}</td>
                    <td>{{ $montant($ligne['encaisse']) }}</td>
                    <td>{{ $montant($ligne['frais_livraison']) }}</td>
                    <td>{{ $montant($ligne['panier_moyen']) }}</td>
                </tr>
            @empty
                <tr><td class="gauche" colspan="10">Aucun vendeur.</td></tr>
            @endforelse
            <tr class="total">
                <td class="gauche">TOTAL</td>
                <td>{{ $totaux['commandes'] }}</td>
                <td>{{ $totaux['livrees'] }}</td>
                <td>{{ $totaux['annulees'] }}</td>
                <td>{{ $totaux['en_cours'] }}</td>
                <td>{{ $totaux['articles_vendus'] }}</td>
                <td>{{ $montant($totaux['chiffre_affaires']) }}</td>
                <td>{{ $montant($totaux['encaisse']) }}</td>
                <td>{{ $montant($totaux['frais_livraison']) }}</td>
                <td>{{ $montant($totaux['panier_moyen']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="pied">
        Commandes créées dans le mois. Montants en FCFA, hors commandes annulées ; « Encaissé » = paiements confirmés. ★ = vendeur en vedette.
    </div>
</body>
</html>
