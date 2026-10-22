<?php

declare(strict_types=1);

use Nrv\Infrastructure\Http\Action\AccueilAction;
use Nrv\Infrastructure\Http\Action\GetBilletAction;
use Nrv\Infrastructure\Http\Action\GetBilletsCommandeAction;
use Nrv\Infrastructure\Http\Action\GetDatesAction;
use Nrv\Infrastructure\Http\Action\GetJaugesAction;
use Nrv\Infrastructure\Http\Action\GetMesBilletsAction;
use Nrv\Infrastructure\Http\Action\GetMesCommandesAction;
use Nrv\Infrastructure\Http\Action\GetReferentielsAdminAction;
use Nrv\Infrastructure\Http\Action\GetSoireeDetailAction;
use Nrv\Infrastructure\Http\Action\GetSpectacleAction;
use Nrv\Infrastructure\Http\Action\GetSpectaclesAction;
use Nrv\Infrastructure\Http\Action\GetStylesAction;
use Nrv\Infrastructure\Http\Action\PostSoireeAction;
use Nrv\Infrastructure\Http\Action\PostSpectacleAction;
use Nrv\Infrastructure\Http\Middleware\AuthentificationMiddleware;
use Nrv\Infrastructure\Http\Middleware\RoleAdminMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {
    // les requetes preflight OPTIONS sont traitees par le middleware cors
    $app->get('/', AccueilAction::class);

    // programme du festival
    $app->get('/spectacles', GetSpectaclesAction::class);
    $app->get('/spectacles/{id}', GetSpectacleAction::class);
    $app->get('/soirees/{id}', GetSoireeDetailAction::class);

    // listes des filtres
    $app->get('/styles', GetStylesAction::class);
    $app->get('/dates', GetDatesAction::class);

    // un billet par son jeton : public, le jeton aleatoire sert de cle (F10)
    $app->get('/billets/{token}', GetBilletAction::class);

    // espace client : utilisateur connecte (jeton jwt)
    $app->group('', function (RouteCollectorProxy $groupe): void {
        $groupe->get('/commandes', GetMesCommandesAction::class);
        $groupe->get('/commandes/{id}/billets', GetBilletsCommandeAction::class);
        $groupe->get('/billets', GetMesBilletsAction::class);
    })->add(AuthentificationMiddleware::class);

    // backoffice : connecte + role ADMIN (le dernier middleware ajoute s'execute en premier)
    $app->group('/admin', function (RouteCollectorProxy $groupe): void {
        $groupe->get('/jauges', GetJaugesAction::class);
        $groupe->get('/referentiels', GetReferentielsAdminAction::class);
        $groupe->post('/soirees', PostSoireeAction::class);
        $groupe->post('/soirees/{id}/spectacles', PostSpectacleAction::class);
    })->add(RoleAdminMiddleware::class)->add(AuthentificationMiddleware::class);
};
