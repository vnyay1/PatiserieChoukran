<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Callback NotchPay pointant sur l'API (NOTCHPAY_CALLBACK_URL=…/payments/callback) : renvoi vers la SPA
Route::get('/payments/callback', function (Request $request) {
    $query = $request->getQueryString();

    return redirect()->away(config('app.frontend_url').'/paiement/retour'.($query ? '?'.$query : ''));
});
