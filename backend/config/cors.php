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

    'exposed_headers' => [],

    'max_age' => 0,

    // Authentification par token Bearer (en-tête), pas par cookie
    'supports_credentials' => false,
];
