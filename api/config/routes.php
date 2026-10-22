<?php

declare(strict_types=1);

use Nrv\Infrastructure\Http\Action\AccueilAction;
use Nrv\Infrastructure\Http\Action\ConnexionAction;
use Nrv\Infrastructure\Http\Action\GetCompteAction;
use Nrv\Infrastructure\Http\Action\GetDatesAction;
use Nrv\Infrastructure\Http\Action\GetPassAction;
use Nrv\Infrastructure\Http\Action\GetSoireeDetailAction;
use Nrv\Infrastructure\Http\Action\GetSoireesAction;
use Nrv\Infrastructure\Http\Action\GetSpectacleAction;
use Nrv\Infrastructure\Http\Action\GetSpectaclesAction;
use Nrv\Infrastructure\Http\Action\GetStylesAction;
use Nrv\Infrastructure\Http\Action\InscriptionAction;
use Nrv\Infrastructure\Http\Middleware\AuthMiddleware;
use Slim\App;

return static function (App $app): void {
    // les requetes preflight OPTIONS sont traitees par le middleware cors
    $app->get('/', AccueilAction::class);

    // programme du festival
    $app->get('/spectacles', GetSpectaclesAction::class);
    $app->get('/spectacles/{id}', GetSpectacleAction::class);
    $app->get('/soirees', GetSoireesAction::class);
    $app->get('/soirees/{id}', GetSoireeDetailAction::class);

    // listes des filtres
    $app->get('/styles', GetStylesAction::class);
    $app->get('/dates', GetDatesAction::class);

    // formule Pass 3 jours
    $app->get('/pass', GetPassAction::class);

    // comptes : inscription, connexion (jeton jwt), compte de l'utilisateur connecte
    $app->post('/auth/register', InscriptionAction::class);
    $app->post('/auth/signin', ConnexionAction::class);
    $app->get('/compte', GetCompteAction::class)->add(AuthMiddleware::class);

    // a l'etape 7, le backoffice sera reserve aux administrateurs :
    // $app->group('/admin', ...)->add(AdminMiddleware::class)->add(AuthMiddleware::class);
};
