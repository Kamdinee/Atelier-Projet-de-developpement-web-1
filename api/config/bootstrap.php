<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// conteneur d'injection de dependances
$builder = new ContainerBuilder();
$builder->addDefinitions(require __DIR__ . '/settings.php');
$builder->addDefinitions(require __DIR__ . '/dependencies.php');
$container = $builder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

(require __DIR__ . '/middlewares.php')($app);
(require __DIR__ . '/routes.php')($app);

return $app;
