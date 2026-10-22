<?php


declare(strict_types=1);
use Nrv\Infrastructure\Http\Action\GetPassAction;
use Nrv\Infrastructure\Http\Middleware\AdminMiddleware;
use Nrv\Infrastructure\Http\Action\AuthAction;
use Nrv\Infrastructure\Http\Action\InscriptionAction;
use Nrv\Infrastructure\Http\Action\AccueilAction;
use Nrv\Infrastructure\Http\Action\GetDatesAction;
use Nrv\Infrastructure\Http\Action\GetSoireeDetailAction;
use Nrv\Infrastructure\Http\Action\GetSpectacleAction;
use Nrv\Infrastructure\Http\Action\GetSpectaclesAction;
use Nrv\Infrastructure\Http\Action\GetStylesAction;
use Slim\App;

return static function (App $app): void {
    
    // les requetes preflight OPTIONS sont traitees par le middleware cors

    $app->get('/', AccueilAction::class);
    // Authentification
$app->post('/auth', AuthAction::class);
    $app->post('/inscription', InscriptionAction::class);

    // programme du festival
    $app->get('/spectacles', GetSpectaclesAction::class);
    $app->get('/spectacles/{id}', GetSpectacleAction::class);
    $app->get('/soirees/{id}', GetSoireeDetailAction::class);

    // listes des filtres
    $app->get('/styles', GetStylesAction::class);
    $app->get('/dates', GetDatesAction::class);
    $app->get('/pass', GetPassAction::class);

    // Backoffice : liste des pass réservée aux administrateurs
    $app->get('/admin/pass', GetPassAction::class)->add(AdminMiddleware::class);
};
