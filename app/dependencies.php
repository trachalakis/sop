<?php

declare(strict_types=1);

use DI\Container;
use Domain\Entities\Invoice;
use Domain\Entities\Language;
use Domain\Entities\Menu;
use Domain\Entities\MenuSection;
use Domain\Entities\MenuItem;
use Domain\Entities\MenuItemExtra;
use Domain\Entities\Order;
use Domain\Entities\OrderEntry;
use Domain\Entities\OrderEntryCancellation;
use Domain\Entities\OrderEntryGroup;
use Domain\Entities\PoString;
use Domain\Entities\PriceList;
use Domain\Entities\Recipe;
use Domain\Entities\Reservation;
use Domain\Entities\Station;
use Domain\Entities\Scan;
use Domain\Entities\ShoppingList;
use Domain\Entities\Supplier;
use Domain\Entities\Supply;
use Domain\Entities\SupplyGroup;
use Domain\Entities\Table;
use Domain\Entities\User;
use Domain\Entities\UserPermission;
use Domain\Repositories\LanguagesRepositoryInterface;
use Domain\Repositories\MenuItemsRepositoryInterface;
use Domain\Repositories\MenusRepositoryInterface;
use Domain\Repositories\MenuSectionsRepositoryInterface;
use Domain\Repositories\OrderEntriesRepositoryInterface;
use Domain\Repositories\MenuItemExtrasRepositoryInterface;
use Domain\Repositories\OrdersRepositoryInterface;
use Domain\Repositories\UserPermissionsRepositoryInterface;
use Domain\Repositories\RecipesRepositoryInterface;
use Domain\Repositories\SuppliesRepositoryInterface;
use Domain\Repositories\ScansRepositoryInterface;
use Domain\Repositories\UsersRepositoryInterface;
use Domain\Repositories\ReservationsRepositoryInterface;
use Domain\Repositories\OrderEntryCancellationsRepositoryInterface;
use Domain\Repositories\TablesRepositoryInterface;
use Domain\Repositories\StationsRepositoryInterface;
use Domain\Repositories\OrderEntryGroupsRepositoryInterface;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Slim\Views\Twig;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Application\Settings\Settings;

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

        },
        LoggerInterface::class => function (ContainerInterface $c) {
            //TODO get logger settings from settings file
            $settings = $c->get('settings');

            //$loggerSettings = $settings->get('logger');

            $logger = new Logger($c->get('logger.name'));

            //$processor = new UidProcessor();
            //$logger->pushProcessor($processor);

            $logger->pushHandler(RotatingFileHandler($c->get('logger.fileName')));

            //$handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            //$logger->pushHandler($handler);

            return $logger;
        },*/
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
        /*TwigMiddleware::class => function (ContainerInterface $container) {
            return TwigMiddleware::createFromContainer(
                $container->get(App::class),
                Twig::class
            );
        },*/
        EntityManagerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get('settings')['db'];

            return Doctrine\ORM\EntityManager::create(
                [
                    'driver' => 'pdo_mysql',
                    'host' => $settings['host'],
                    'user' => $settings['username'],
                    'password' => $settings['password'],
                    'dbname' => $settings['databaseName'],
                ],
                //TODO
                Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration([__DIR__ . '/../src/Domain/Entities'], $isDevMode = true)
            );
        },
        LanguagesRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(Language::class);
        },
        MenusRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(Menu::class);
        },
        MenuSectionsRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(MenuSection::class);
        },
        MenuItemsRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(MenuItem::class);
        },
        MenuItemExtrasRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(MenuItemExtra::class);
        },
        OrdersRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(Order::class);
        },
        OrderEntriesRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(OrderEntry::class);
        },
        OrderEntryCancellationsRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(OrderEntryCancellation::class);
        },
        OrderEntryGroupsRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(OrderEntryGroup::class);
        },
        StationsRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(Station::class);
        },
        TablesRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(Table::class);
        },
        ReservationsRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(Reservation::class);
        },
        UsersRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(User::class);
        },
        ScansRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(Scan::class);
        },
        SuppliesRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(Supply::class);
        },
        /*SuppliersRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(Supplier::class);
        },
        'Domain\Repositories\SuppliesRepositoryInterface' => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(Supply::class);
        },
        'Domain\Repositories\InvoicesRepositoryInterface' => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(Invoice::class);
        },*/
        RecipesRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(Recipe::class);
        },
        /*'Domain\Repositories\PoStringsRepositoryInterface' => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(PoString::class);
        },*/
        UserPermissionsRepositoryInterface::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(UserPermission::class);
        },
        /*
        'Domain\Repositories\SupplyGroupsRepositoryInterface' => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(SupplyGroup::class);
        },
        'Domain\Repositories\ShoppingListsRepositoryInterface' => function (ContainerInterface $c) {
            $em = $c->get(EntityManagerInterface::class);

            return $em->getRepository(ShoppingList::class);
        }*/
    ]);
};