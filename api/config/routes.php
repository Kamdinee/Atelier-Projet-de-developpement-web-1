<?php

declare(strict_types=1);

use Nrv\Infrastructure\Http\Action\AccueilAction;
use Slim\App;

return static function (App $app): void {
    // reponse vide aux requetes preflight
    $app->options('/{routes:.+}', static fn ($request, $response) => $response);

    $app->get('/', AccueilAction::class);
};
