<?php

declare(strict_types=1);

// lecture d'une variable d'env avec valeur par defaut
$env = static fn (string $nom, string $defaut = ''): string => getenv($nom) !== false ? (string) getenv($nom) : $defaut;

return [
    'settings' => [
        'env' => $env('APP_ENV', 'dev'),
        'db' => [
            'host' => $env('DB_HOST', 'db'),
            'port' => $env('DB_PORT', '3306'),
            'name' => $env('DB_NAME', 'nrv'),
            'user' => $env('DB_USER', 'nrv'),
            'password' => $env('DB_PASSWORD', 'nrv'),
        ],
        'jwt' => [
            'secret' => $env('JWT_SECRET', 'change-moi-cle-de-dev-de-32-caracteres-min'),
            'ttl' => (int) $env('JWT_TTL', '3600'),
        ],
        // liste separee par des virgules (CORS_ORIGIN seul reste accepte pour les anciens .env)
        'cors' => [
            'origines' => array_values(array_filter(array_map(
                'trim',
                explode(',', $env('CORS_ORIGINS', $env('CORS_ORIGIN', 'http://localhost:8081,http://127.0.0.1:8081')))
            ))),
        ],
    ],
];
