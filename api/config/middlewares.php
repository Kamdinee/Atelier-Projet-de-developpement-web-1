<?php

declare(strict_types=1);

use Nrv\Infrastructure\Http\Error\ProblemJsonErrorHandler;
use Nrv\Infrastructure\Http\Middleware\CorsMiddleware;
use Slim\App;

return static function (App $app): void {
    $settings = $app->getContainer()->get('settings');

    // slim execute le dernier ajoute en premier :
    // cors -> erreurs -> routage -> lecture du corps -> action
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    $erreurs = $app->addErrorMiddleware($settings['env'] === 'dev', true, true);
    $erreurs->setDefaultErrorHandler(new ProblemJsonErrorHandler($app->getResponseFactory()));

    // en dernier pour que les reponses d'erreur aient aussi les entetes cors
    $app->add(new CorsMiddleware($settings['cors']['origines']));
};
