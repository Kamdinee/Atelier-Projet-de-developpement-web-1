<?php

declare(strict_types=1);
use Nrv\Application\Port\Out\PassRepositoryInterface;
use Nrv\Application\UseCase\ListerPass;
use Nrv\Infrastructure\Persistence\Pdo\PDOPassRepository;
use Nrv\Application\Port\In\ConsulterSoireeInterface;
use Nrv\Application\Port\In\ConsulterSpectacleInterface;
use Nrv\Application\Port\In\ListerFiltresProgrammeInterface;
use Nrv\Application\Port\In\ListerSpectaclesInterface;
use Nrv\Application\Port\Out\SoireeRepositoryInterface;
use Nrv\Application\Port\Out\SpectacleRepositoryInterface;
use Nrv\Application\Port\Out\UtilisateurRepositoryInterface;
use Nrv\Application\UseCase\AuthentifierUtilisateur;
use Nrv\Application\UseCase\InscrireUtilisateur;
use Nrv\Application\UseCase\ConsulterSoiree;
use Nrv\Application\UseCase\ConsulterSpectacle;
use Nrv\Application\UseCase\ListerFiltresProgramme;
use Nrv\Application\UseCase\ListerSpectacles;
use Nrv\Infrastructure\Persistence\Pdo\PDOSoireeRepository;
use Nrv\Infrastructure\Persistence\Pdo\PDOSpectacleRepository;
use Nrv\Infrastructure\Persistence\Pdo\PDOUtilisateurRepository;
use Nrv\Infrastructure\Security\PasswordHasher;
use Nrv\Infrastructure\Token\JwtTokenService;
use Psr\Container\ContainerInterface;
use Nrv\Infrastructure\Http\Middleware\AdminMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;

use function DI\autowire;


return [
  
    PDO::class => static function (ContainerInterface $c): PDO {
        $db = $c->get('settings')['db'];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $db['host'],
            $db['port'],
            $db['name']
        );
        

        return new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    },

    SpectacleRepositoryInterface::class => autowire(PDOSpectacleRepository::class),
    SoireeRepositoryInterface::class => autowire(PDOSoireeRepository::class),
    UtilisateurRepositoryInterface::class => autowire(PDOUtilisateurRepository::class),

   
    ListerSpectaclesInterface::class => autowire(ListerSpectacles::class),
    ConsulterSpectacleInterface::class => autowire(ConsulterSpectacle::class),
    ConsulterSoireeInterface::class => autowire(ConsulterSoiree::class),
    ListerFiltresProgrammeInterface::class => autowire(ListerFiltresProgramme::class),

   
    PasswordHasher::class => autowire(PasswordHasher::class),

    
    JwtTokenService::class => static function (ContainerInterface $c): JwtTokenService {
        $jwt = $c->get('settings')['jwt'];

        return new JwtTokenService(
            (string) $jwt['secret'],
            (int) $jwt['ttl'],
        );
    },
    ResponseFactoryInterface::class => static function (ContainerInterface $c): ResponseFactoryInterface {
    return \Slim\Factory\AppFactory::determineResponseFactory();
},

AdminMiddleware::class => autowire(AdminMiddleware::class),

    AuthentifierUtilisateur::class => autowire(AuthentifierUtilisateur::class),
    InscrireUtilisateur::class => autowire(InscrireUtilisateur::class),
    PassRepositoryInterface::class => autowire(PDOPassRepository::class),

    // Commandes & Paiement
    \Nrv\Application\Port\Out\CommandeRepositoryInterface::class => autowire(\Nrv\Infrastructure\Persistence\Pdo\PDOCommandeRepository::class),
    \Nrv\Infrastructure\Http\Middleware\AuthMiddleware::class => autowire(\Nrv\Infrastructure\Http\Middleware\AuthMiddleware::class),
    \Nrv\Application\UseCase\CreerCommande::class => autowire(\Nrv\Application\UseCase\CreerCommande::class),
    \Nrv\Application\UseCase\PayerCommande::class => autowire(\Nrv\Application\UseCase\PayerCommande::class),
    \Nrv\Application\UseCase\ConsulterCommande::class => autowire(\Nrv\Application\UseCase\ConsulterCommande::class),
];
