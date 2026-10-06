<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Origines autorisées, séparées par des virgules (ex. https://choukrane.cm). Par défaut,
    // seulement l'adresse du SPA (FRONTEND_URL) ; vide : aucune requête cross-origin (Docker,
    // où le SPA et l'API partagent la même origine). « * » n'est à utiliser qu'en dépannage.
    'allowed_origins' => array_values(array_filter(array_map(
        fn (string $origine) => rtrim(trim($origine), '/'),
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:5173')))
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
