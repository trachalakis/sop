<?php

use DI\ContainerBuilder;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$containerBuilder = new ContainerBuilder;

//Set up settings
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addRoutingMiddleware();

//Logging
$loggerSettings = $container->get('settings')['logger'];
$logger = new Logger($loggerSettings['name']);
$logger->pushHandler(new RotatingFileHandler($loggerSettings['file']));
$errorMiddleware = $app->addErrorMiddleware(
    $container->get('settings')['appMode'] == 'development',
    true,
    true,
    $logger
);

$routes = require __DIR__ . '/../app/routes.php';
$routes($app, $container);

// Run app
$app->run();