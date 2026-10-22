<?php

declare(strict_types=1);

// outil de dev : affiche un jeton jwt pour un compte du jeu de test
// usage : docker compose exec php composer jeton -- admin@nrv.net
// pratique pour tester les routes protegees avec curl sans passer par la page de connexion

use Nrv\Infrastructure\Token\JwtTokenService;

require __DIR__ . '/../vendor/autoload.php';

$settings = (require __DIR__ . '/../config/settings.php')['settings'];
if ($settings['env'] !== 'dev') {
    fwrite(STDERR, "outil reserve a l'environnement de dev\n");
    exit(1);
}

$email = $argv[1] ?? 'client@nrv.net';
$db = $settings['db'];
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']),
    $db['user'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false],
);

$requete = $pdo->prepare('SELECT id, email, role FROM utilisateurs WHERE email = ?');
$requete->execute([$email]);
$utilisateur = $requete->fetch(PDO::FETCH_ASSOC);
if ($utilisateur === false) {
    fwrite(STDERR, "aucun utilisateur {$email}\n");
    exit(1);
}

echo (new JwtTokenService($settings['jwt']['secret'], $settings['jwt']['ttl']))
    ->generate((int) $utilisateur['id'], (string) $utilisateur['role']), PHP_EOL;
