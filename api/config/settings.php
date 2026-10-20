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
            'secret' => $env('JWT_SECRET', 'change-moi'),
            'ttl' => (int) $env('JWT_TTL', '3600'),
        ],
        'cors' => [
            'origin' => $env('CORS_ORIGIN', 'http://localhost:8081'),
        ],
    ],
];
