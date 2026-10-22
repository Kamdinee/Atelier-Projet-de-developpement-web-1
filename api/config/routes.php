<?php

declare(strict_types=1);

use Nrv\Infrastructure\Http\Action\AccueilAction;
use Nrv\Infrastructure\Http\Action\GetBilletAction;
use Nrv\Infrastructure\Http\Action\GetDatesAction;
use Nrv\Infrastructure\Http\Action\GetSoireeDetailAction;
use Nrv\Infrastructure\Http\Action\GetSpectacleAction;
use Nrv\Infrastructure\Http\Action\GetSpectaclesAction;
use Nrv\Infrastructure\Http\Action\GetStylesAction;
use Slim\App;

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

    // backoffice : connecte + role ADMIN (le dernier middleware ajoute s'execute en premier)
};
