<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Origines autorisées, séparées par des virgules (ex. https://choukrane.cm).
    // En Docker, le SPA et l'API partagent la même origine : aucune requête cross-origin.
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', '*'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    // Nom des factures et rapports téléchargés (lu par le SPA sur une autre origine en dev)
    'exposed_headers' => ['Content-Disposition'],

    // Requête préalable (OPTIONS) mise en cache par le navigateur, plafonnée à 2 h par Chrome
    'max_age' => 7200,

    // Authentification par token Bearer (en-tête), pas par cookie
    'supports_credentials' => false,
];
