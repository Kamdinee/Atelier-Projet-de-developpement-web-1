<?php

declare(strict_types=1);

use Nrv\Application\Port\In\ConsulterSoireeInterface;
use Nrv\Application\Port\In\ConsulterSpectacleInterface;
use Nrv\Application\Port\In\ListerFiltresProgrammeInterface;
use Nrv\Application\Port\In\ListerSpectaclesInterface;
use Nrv\Application\UseCase\ConsulterSoiree;
use Nrv\Application\UseCase\ConsulterSpectacle;
use Nrv\Application\UseCase\ListerFiltresProgramme;
use Nrv\Application\UseCase\ListerSpectacles;
use Nrv\Domain\Repository\SoireeRepositoryInterface;
use Nrv\Domain\Repository\SpectacleRepositoryInterface;
use Nrv\Infrastructure\Persistence\Pdo\PDOSoireeRepository;
use Nrv\Infrastructure\Persistence\Pdo\PDOSpectacleRepository;
use Psr\Container\ContainerInterface;

use function DI\autowire;

// branchement des ports sur les adaptateurs
// les actions, validateurs et classes concretes sont crees automatiquement (autowiring)
return [
    // connexion a la base
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

    // ports de sortie (domaine) -> adaptateurs pdo
    SpectacleRepositoryInterface::class => autowire(PDOSpectacleRepository::class),
    SoireeRepositoryInterface::class => autowire(PDOSoireeRepository::class),

    // ports d'entree (application) -> cas d'usage
    ListerSpectaclesInterface::class => autowire(ListerSpectacles::class),
    ConsulterSpectacleInterface::class => autowire(ConsulterSpectacle::class),
    ConsulterSoireeInterface::class => autowire(ConsulterSoiree::class),
    ListerFiltresProgrammeInterface::class => autowire(ListerFiltresProgramme::class),
];
