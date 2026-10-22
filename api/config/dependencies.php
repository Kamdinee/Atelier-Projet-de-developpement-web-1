<?php

declare(strict_types=1);

use Nrv\Application\Port\In\AuthentifierUtilisateurInterface;
use Nrv\Application\Port\In\ConsulterBilletInterface;
use Nrv\Application\Port\In\ConsulterCompteInterface;
use Nrv\Application\Port\In\ConsulterJaugesInterface;
use Nrv\Application\Port\In\ConsulterMesAchatsInterface;
use Nrv\Application\Port\In\ConsulterPassInterface;
use Nrv\Application\Port\In\ConsulterSoireeInterface;
use Nrv\Application\Port\In\ConsulterSpectacleInterface;
use Nrv\Application\Port\In\GererProgrammeInterface;
use Nrv\Application\Port\In\InscrireUtilisateurInterface;
use Nrv\Application\Port\In\ListerFiltresProgrammeInterface;
use Nrv\Application\Port\In\ListerSoireesInterface;
use Nrv\Application\Port\In\ListerSpectaclesInterface;
use Nrv\Application\Port\Out\BilletRepositoryInterface;
use Nrv\Application\Port\Out\CommandeLectureRepositoryInterface;
use Nrv\Application\Port\Out\HacheurMotDePasseInterface;
use Nrv\Application\Port\Out\JaugeRepositoryInterface;
use Nrv\Application\Port\Out\JetonInterface;
use Nrv\Application\Port\Out\PassRepositoryInterface;
use Nrv\Application\Port\Out\ProgrammeAdminRepositoryInterface;
use Nrv\Application\Port\Out\SoireeRepositoryInterface;
use Nrv\Application\Port\Out\SpectacleRepositoryInterface;
use Nrv\Application\Port\Out\UtilisateurRepositoryInterface;
use Nrv\Application\UseCase\AuthentifierUtilisateur;
use Nrv\Application\UseCase\ConsulterBillet;
use Nrv\Application\UseCase\ConsulterCompte;
use Nrv\Application\UseCase\ConsulterJauges;
use Nrv\Application\UseCase\ConsulterMesAchats;
use Nrv\Application\UseCase\ConsulterPass;
use Nrv\Application\UseCase\ConsulterSoiree;
use Nrv\Application\UseCase\ConsulterSpectacle;
use Nrv\Application\UseCase\GererProgramme;
use Nrv\Application\UseCase\InscrireUtilisateur;
use Nrv\Application\UseCase\ListerFiltresProgramme;
use Nrv\Application\UseCase\ListerSoirees;
use Nrv\Application\UseCase\ListerSpectacles;
use Nrv\Infrastructure\Persistence\Pdo\PDOBilletRepository;
use Nrv\Infrastructure\Persistence\Pdo\PDOCommandeLectureRepository;
use Nrv\Infrastructure\Persistence\Pdo\PDOJaugeRepository;
use Nrv\Infrastructure\Persistence\Pdo\PDOPassRepository;
use Nrv\Infrastructure\Persistence\Pdo\PDOProgrammeAdminRepository;
use Nrv\Infrastructure\Persistence\Pdo\PDOSoireeRepository;
use Nrv\Infrastructure\Persistence\Pdo\PDOSpectacleRepository;
use Nrv\Infrastructure\Persistence\Pdo\PDOUtilisateurRepository;
use Nrv\Infrastructure\Security\BcryptHacheur;
use Nrv\Infrastructure\Token\JwtService;
use Psr\Container\ContainerInterface;

use function DI\autowire;
use function DI\get;

// branchement des ports sur les adaptateurs
// les actions, validateurs et middlewares sont crees automatiquement (autowiring)
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

    // jwt : secret et duree de vie lus dans le .env
    JwtService::class => static function (ContainerInterface $c): JwtService {
        $jwt = $c->get('settings')['jwt'];

        return new JwtService((string) $jwt['secret'], (int) $jwt['ttl']);
    },

    // ports de sortie -> adaptateurs
    SpectacleRepositoryInterface::class => autowire(PDOSpectacleRepository::class),
    SoireeRepositoryInterface::class => autowire(PDOSoireeRepository::class),
    UtilisateurRepositoryInterface::class => autowire(PDOUtilisateurRepository::class),
    PassRepositoryInterface::class => autowire(PDOPassRepository::class),
    HacheurMotDePasseInterface::class => autowire(BcryptHacheur::class),
    JetonInterface::class => get(JwtService::class),
    BilletRepositoryInterface::class => autowire(PDOBilletRepository::class),
    CommandeLectureRepositoryInterface::class => autowire(PDOCommandeLectureRepository::class),
    JaugeRepositoryInterface::class => autowire(PDOJaugeRepository::class),
    ProgrammeAdminRepositoryInterface::class => autowire(PDOProgrammeAdminRepository::class),

    // ports d'entree -> cas d'usage
    ListerSpectaclesInterface::class => autowire(ListerSpectacles::class),
    ConsulterSpectacleInterface::class => autowire(ConsulterSpectacle::class),
    ConsulterSoireeInterface::class => autowire(ConsulterSoiree::class),
    ListerSoireesInterface::class => autowire(ListerSoirees::class),
    ListerFiltresProgrammeInterface::class => autowire(ListerFiltresProgramme::class),
    ConsulterPassInterface::class => autowire(ConsulterPass::class),
    InscrireUtilisateurInterface::class => autowire(InscrireUtilisateur::class),
    AuthentifierUtilisateurInterface::class => autowire(AuthentifierUtilisateur::class),
    ConsulterCompteInterface::class => autowire(ConsulterCompte::class),
    ConsulterBilletInterface::class => autowire(ConsulterBillet::class),
    ConsulterMesAchatsInterface::class => autowire(ConsulterMesAchats::class),
    ConsulterJaugesInterface::class => autowire(ConsulterJauges::class),
    GererProgrammeInterface::class => autowire(GererProgramme::class),
];
