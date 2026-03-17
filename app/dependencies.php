<?php

declare(strict_types=1);

use Domain\Entities\Language;
use Domain\Entities\Menu;
use Domain\Entities\MenuSection;
use Domain\Entities\MenuItem;
use Domain\Entities\Order;
use Domain\Entities\OrderEntry;
use Domain\Entities\OrderEntryCancellation;
use Domain\Entities\OrderEntryGroup;
use Domain\Entities\Reservation;
use Domain\Entities\Printer;
use Domain\Entities\PrintJob;
use Domain\Entities\Scan;
use Domain\Entities\Supply;
use Domain\Entities\SupplyGroup;
use Domain\Entities\Table;
use Domain\Entities\User;
use Domain\Entities\UserPermission;
use Domain\Repositories\LanguagesRepository;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\MenusRepository;
use Domain\Repositories\MenuSectionsRepository;
use Domain\Repositories\OrderEntriesRepository;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\PrintJobsRepository;
use Domain\Repositories\UserPermissionsRepository;
use Domain\Repositories\SuppliesRepository;
use Domain\Repositories\ScansRepository;
use Domain\Repositories\UsersRepository;
use Domain\Repositories\OrderEntryCancellationsRepository;
use Domain\Repositories\ReservationsRepository;
use Domain\Repositories\TablesRepository;
use Domain\Repositories\PrintersRepository;
use Domain\Repositories\OrderEntryGroupsRepository;
use Domain\Repositories\SupplyGroupsRepository;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;
use Application\Settings\Settings;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;


return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        /*Mailer::class => function (ContainerInterface $c) {
            $settings = $c->get('settings')['mailer'];

            $transport = Transport::fromDsn(sprintf('smtp://%s:%s@%s:%s',
            	$settings['username'],
            	$settings['password'],
            	$settings['host'],
            	$settings['port']
        	));

            return $mailer = new Mailer($transport);

        },*/
        Logger::class => function (ContainerInterface $c) {
            $loggerSettings = $c->get('settings')['logger'];

            $logger = new Logger($loggerSettings['name']);
            $logger->pushHandler(new RotatingFileHandler($loggerSettings['file']));

            return $logger;
        },
        Twig::class => function (ContainerInterface $c) {
            $settings = $c->get('settings')['twig'];

            $options = $settings['options'];
            $options['cache'] = $options['cacheEnabled'] ? $options['cachePath'] : false;

            $twig = Twig::create($settings['paths'], $options);

            $twig->getEnvironment()->addFilter(new \Twig\TwigFilter('_', function ($string) {
   				return gettext(trim(preg_replace('/\s+/', ' ', $string)));

   				//return $string;
   			}));

            return $twig;
        },
        Settings::class => function (ContainerInterface $c) {
            $settings = $c->get('settings');

            return new Settings($settings);
        },
        EntityManager::class => function (ContainerInterface $c) {
            $dbSettings = $c->get('settings')['db'];

            $config = ORMSetup::createAttributeMetadataConfig(
                paths: [__DIR__ . '/../src/Domain/Entities'],
                isDevMode: true,
            );
            $config->enableNativeLazyObjects(true);
            $config->addCustomDatetimeFunction('DATE', DoctrineExtensions\Query\Postgresql\Date::class);
            
            $eventManager = new Doctrine\Common\EventManager();
            $eventManager->addEventListener(
                [Doctrine\ORM\Events::onFlush], 
                new Application\EventListeners\OnFlushListener($c)
            );
            
            $connection = DriverManager::getConnection([
                'driver' => 'pdo_pgsql',
                'host' => $dbSettings['host'],
                'user' => $dbSettings['username'],
                'password' => $dbSettings['password'],
                'dbname' => $dbSettings['databaseName'],
            ], $config);


            return new EntityManager($connection, $config, $eventManager);
        },
        LanguagesRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(Language::class);
        },
        MenusRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(Menu::class);
        },
        MenuSectionsRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(MenuSection::class);
        },
        MenuItemsRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(MenuItem::class);
        },
        OrdersRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(Order::class);
        },
        OrderEntriesRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(OrderEntry::class);
        },
        OrderEntryCancellationsRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(OrderEntryCancellation::class);
        },
        OrderEntryGroupsRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(OrderEntryGroup::class);
        },
        ReservationsRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(Reservation::class);
        },
        PrintersRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(Printer::class);
        },
        TablesRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(Table::class);
        },
        UsersRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(User::class);
        },
        ScansRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(Scan::class);
        },
        SuppliesRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(Supply::class);
        },
        SupplyGroupsRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(SupplyGroup::class);
        },
        PrintJobsRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(PrintJob::class);
        },
        /*'Domain\Repositories\PoStringsRepository' => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(PoString::class);
        },*/
        UserPermissionsRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);

            return $em->getRepository(UserPermission::class);
        },
        'SessionUser' => function (ContainerInterface $c) {
            return $_SESSION['user'] ?? null;
        },
    ]);
};