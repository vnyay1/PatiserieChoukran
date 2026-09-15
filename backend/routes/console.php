<?php

use App\Models\Panier;
use App\Services\NotificationsCompte;
use App\Services\RapportMensuelVendeurs;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('panier:purge-expired', function () {
    $count = Panier::purgerExpires();
    $this->info("Paniers expirés supprimés: {$count}");
})->purpose('Supprime les paniers expirés');

Artisan::command('rapports:mensuels {--mois= : Mois au format AAAA-MM (par défaut : le mois précédent)}', function () {
    $mois = $this->option('mois')
        ? RapportMensuelVendeurs::mois($this->option('mois'))
        : CarbonImmutable::now()->startOfMonth()->subMonthNoOverflow();

    if (! $mois) {
        $this->error('Mois invalide : utilisez le format AAAA-MM.');

        return 1;
    }

    $chemins = RapportMensuelVendeurs::stocker($mois);
    NotificationsCompte::rapportMensuelDisponible(RapportMensuelVendeurs::libelle($mois), $mois->format('Y-m'));

    $this->info('Rapport '.RapportMensuelVendeurs::libelle($mois).' : '.implode(', ', $chemins));

    return 0;
})->purpose('Génère le rapport mensuel des vendeurs (PDF et CSV) et prévient les admins');

// Paniers restés sans modification au-delà de la durée réglée par l'admin : vidés
// toutes les 5 minutes (les endpoints du panier les vident aussi à la volée)
Schedule::command('panier:purge-expired')->everyFiveMinutes();

// Le 1er de chaque mois : rapport du mois écoulé, prêt dans l'espace admin
Schedule::command('rapports:mensuels')->monthlyOn(1, '06:00');
