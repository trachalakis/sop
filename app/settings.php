<?php

declare(strict_types=1);

use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {

    //Gradually witch to .env for settings
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../app');
    $dotenv->load();

    

    // Global Settings Object
    $containerBuilder->addDefinitions([
        'settings' => [
            'slim.displayErrorDetails' => $_ENV['APP_MODE'] == 'development', // Should be set to false in production'
            'slim.logError' => $_ENV['APP_MODE'] == 'development',
            'slim.logErrorDetails' => $_ENV['APP_MODE'] == 'development',

            'logger' => [
                'name' => 'sopApp',
                'file' => __DIR__ . '/../logs/app.log'
            ],
            'twig' => [
                // Template paths
                'paths' => [
                    __DIR__ . '/../src/templates',
                ],
                // Twig environment options
                'options' => [
                    // Should be set to true in production
                    'cacheEnabled' => true,
		    'cachePath' => '/tmp',
		    'auto_reload' => true
                ],
            ],
            'db' => [
                'host' => $_ENV['DB_HOST'],
                'username' => $_ENV['DB_USERNAME'],
                'password' => $_ENV['DB_PASSWORD'],
                'databaseName' => $_ENV['DB_DATABASE_NAME']
            ],
            'siteName' => $_ENV['SITE_NAME']
            /*,
            'mailer' => [
            	'username' => '',
            	'password' => '',
            	'host' => '',
            	'port' => ''
            ]*/
        ]
    ]);
};
