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
            'logger' => [
                'name' => 'sopApp',
                'file' => __DIR__ . '/../logs/app.log'
            ],
            'twig' => [
                'paths' => [
                    __DIR__ . '/../src/templates',
                ],
                'options' => [
                    'cacheEnabled' => $_ENV['APP_MODE'] == 'production',
		            'cachePath' => '/tmp',
		            'auto_reload' => true
                ],
            ],
            'db' => [
                'host' => $_ENV['DB_HOST'],
                'username' => $_ENV['DB_USERNAME'],
                'password' => $_ENV['DB_PASSWORD'],
                'databaseName' => $_ENV['DB_NAME']
            ],
            'siteName' => $_ENV['SITE_NAME'],
            'appMode' => $_ENV['APP_MODE']
        ]
    ]);
};
