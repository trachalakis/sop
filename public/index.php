<?php

use DI\ContainerBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$containerBuilder = new ContainerBuilder();
//Set up settings
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);
// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();
// Add Routing Middleware
$app->addRoutingMiddleware();
/*$app->add(function (
    ServerRequestInterface $request,
    RequestHandlerInterface $handler
    ) {
    try {
        return $handler->handle($request);
    } catch (HttpNotFoundException $httpException) {
        $response = (new Response())->withStatus(404);
        $response->getBody()->write('404 Not found');

        return $response;
    }
});*/
// Add Error Middleware
/* Monolog Example
$logger = new Logger('app');
$streamHandler = new StreamHandler(__DIR__ . '/var/log', 100);
$logger->pushHandler($streamHandler);

// Add Error Middleware with Logger
$errorMiddleware = $app->addErrorMiddleware(true, true, true, $logger);
*/
//$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logError, $logErrorDetails);
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$routes = require __DIR__ . '/../app/routes.php';
$routes($app, $container);

// Run app
$app->run();