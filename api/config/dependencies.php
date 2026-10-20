<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;

// branchement des ports sur les adaptateurs (complete a chaque etape)
return [
    PDO::class => static function (ContainerInterface $c): PDO {
        $db = $c->get('settings')['db'];
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']);

        return new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // vraies requetes preparees cote serveur
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    },
];
