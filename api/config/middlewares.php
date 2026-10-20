<?php

declare(strict_types=1);

use Nrv\Infrastructure\Http\Middleware\CorsMiddleware;
use Slim\App;

return static function (App $app): void {
    $settings = $app->getContainer()->get('settings');

    // slim execute le dernier ajoute en premier
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();
    $app->addErrorMiddleware($settings['env'] === 'dev', true, true);
    $app->add(new CorsMiddleware($settings['cors']['origin']));
};
