<?php

use App\Models\Panier;
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

// Les endpoints du panier purgent aussi à la volée : une passe par heure suffit
Schedule::command('panier:purge-expired')->hourly();
