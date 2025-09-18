<?php

declare(strict_types=1);

use Application\Actions\Clock;
use Application\Actions\Homepage;
use Application\Actions\Login;
use Application\Actions\Logout;
use Application\Actions\Test;
use Application\Actions\Admin\CloneMenu;
use Application\Actions\Admin\CloneMenuItem;
use Application\Actions\Admin\CreateInvoice;
use Application\Actions\Admin\CreateMenuItem;
use Application\Actions\Admin\CreateMenuSection;
use Application\Actions\Admin\CreateRecipe;
//use Application\Actions\Admin\CreateReservation;
use Application\Actions\Admin\CreateScan;
use Application\Actions\Admin\CreateShoppingList;
use Application\Actions\Admin\CreateSupply;
use Application\Actions\Admin\CreateTable;
use Application\Actions\Admin\CreateUser;
use Application\Actions\Admin\DeleteOrder;
use Application\Actions\Admin\DeleteReservation;
use Application\Actions\Admin\DeleteScan;
use Application\Actions\Admin\DeleteMenuItem;
use Application\Actions\Admin\GraphQl;
use Application\Actions\Admin\Homepage as AdminHomepage;
use Application\Actions\Admin\Invoices;
use Application\Actions\Admin\Menu as AdminMenu;
//use Application\Actions\Admin\Menus;
use Application\Actions\Admin\MenuItemRecipe;
use Application\Actions\Admin\MenuItemStatistics;
use Application\Actions\Admin\MenuSectionStatistics;
use Application\Actions\Admin\OpenOrder;
use Application\Actions\Admin\PriceLists;
use Application\Actions\Admin\Predict;
use Application\Actions\Admin\PrintMenu;
use Application\Actions\Admin\PoStrings;
use Application\Actions\Admin\Recipes;
use Application\Actions\Admin\Report;
use Application\Actions\Admin\Reservations as AdminReservations;
use Application\Actions\Admin\Scans;
use Application\Actions\Admin\MakeShoppingList;
use Application\Actions\Admin\ShoppingLists;
use Application\Actions\Admin\Suppliers;
use Application\Actions\Admin\Supplies;
use Application\Actions\Admin\SortMenuItems;
use Application\Actions\Admin\SortMenuSections;
use Application\Actions\Admin\Tables;
use Application\Actions\Admin\ToggleMenuItem;
use Application\Actions\Admin\UpdateMenuItem;
use Application\Actions\Admin\UpdateMenuSection;
use Application\Actions\Admin\ViewOrder;
use Application\Actions\Admin\UpdateRecipe;
//use Application\Actions\Admin\UpdateReservation;
use Application\Actions\Admin\UpdateScan;
use Application\Actions\Admin\UpdateShoppingList;
use Application\Actions\Admin\UpdateSupplier;
use Application\Actions\Admin\UpdateSupply;
use Application\Actions\Admin\UpdateTable;
use Application\Actions\Admin\UpdateUser;
use Application\Actions\Admin\UserQrCode;
use Application\Actions\Admin\Users;
use Application\Actions\Admin\UserScans;
use Application\Actions\Admin\UserOrders;
use Application\Actions\Admin\ViewInvoice;
use Application\Actions\OrdersApp\Homepage as OrdersAppHomepage;
use Application\Actions\OrdersApp\Timeline;
use Application\Actions\OrdersApp\CatchOfTheDay;
use Application\Actions\OrdersApp\CreateOrder;
use Application\Actions\OrdersApp\OrderPayment;
use Application\Actions\OrdersApp\PrintOrderReceipt;
use Application\Actions\OrdersApp\UpdateOrder;
use Application\Actions\OrdersApp\TakeOut;
use Application\Actions\ReservationsApp\Homepage as ReservationsAppHomepage;
use Application\Actions\ReservationsApp\CreateReservation;
use Application\Actions\ReservationsApp\UpdateReservation;
use Application\Actions\ReservationsApp\PrintReservations;
use Application\Actions\ReservationsApp\TabularView;
use Application\Actions\UsersApp\Homepage as UsersAppHomepage;
use Application\Actions\UsersApp\Scans as UsersAppScans;
use Application\Actions\UsersApp\Orders as UsersAppOrders;
use Application\Actions\UsersApp\CreateOrder as UsersAppCreateOrder;
use Application\Actions\UsersApp\ViewOrder as UsersAppViewOrder;
use Application\Actions\UsersApp\UpdatePin as UsersAppUpdatePin;
use Middleware\Authentication;
use Middleware\Authorization;
use Middleware\Globals;
use Middleware\Menus;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app, $container) {
    $app->get('/[{language:[a-z]{2}}]', Homepage::class);
    $app->get('/this-is-a-test', Test::class);

    $app->map(['GET', 'POST'], '/clock', Clock::class);

    $app->map(['GET', 'POST'], '/login', Login::class);
    $app->get('/logout', Logout::class);

    $app->group('/admin', function (RouteCollectorProxy $group) {
        $group->get('/', AdminHomepage::class);

        $group->get('/orders/open', OpenOrder::class);
        $group->get('/orders/view', ViewOrder::class);
        $group->get('/orders/delete', DeleteOrder::class);

        $group->get('/reservations', AdminReservations::class);
        //$group->map(['GET', 'POST'], '/reservations/create', CreateReservation::class);
        $group->map(['GET', 'POST'], '/reservations/update', UpdateReservation::class);
        $group->get('/reservations/delete', DeleteReservation::class);

        $group->get('/tables', Tables::class);
        $group->map(['GET', 'POST'], '/tables/create', CreateTable::class);
        $group->map(['GET', 'POST'], '/tables/update', UpdateTable::class);

        $group->get('/menu', AdminMenu::class);
        $group->get('/menu/print', PrintMenu::class);
        $group->map(['GET', 'POST'], '/menu-sections/create', CreateMenuSection::class);
        $group->map(['GET', 'POST'], '/menu-sections/update', UpdateMenuSection::class);
        $group->get('/menu-sections/statistics', MenuSectionStatistics::class);
        $group->map(['GET', 'POST'], '/menu-items/create', CreateMenuItem::class);
        $group->map(['GET', 'POST'], '/menu-items/update', UpdateMenuItem::class);
        $group->map(['GET', 'POST'], '/menu-items/recipe', MenuItemRecipe::class);
        $group->get('/menu-items/statistics', MenuItemStatistics::class);
        $group->get('/menu-items/toggle', ToggleMenuItem::class);
        $group->get('/menu-items/delete', DeleteMenuItem::class);
        $group->get('/menu-items/clone', CloneMenuItem::class);
        $group->post('/menu-items/sort', SortMenuItems::class);
        $group->post('/menu-sections/sort', SortMenuSections::class);

        
        //$group->get('/menus', Menus::class);
        $group->get('/clone-menu', CloneMenu::class);

        $group->get('/price-lists', PriceLists::class);

        $group->get('/users', Users::class);
        $group->map(['GET', 'POST'], '/users/create', CreateUser::class);
        $group->map(['GET', 'POST'], '/users/update', UpdateUser::class);
        $group->get('/users/qr-code', UserQrCode::class);
        $group->get('/users/orders', UserOrders::class);

        $group->get('/scans', Scans::class);
        $group->map(['GET', 'POST'], '/scans/create', CreateScan::class);
        $group->map(['GET', 'POST'], '/scans/update', UpdateScan::class);
        $group->get('/scans/delete', DeleteScan::class);
        $group->get('/scans/user', UserScans::class);

        /*$group->get('/invoices', Invoices::class);
        $group->map(['GET', 'POST'], '/invoices/create', CreateInvoice::class);
        $group->get('/invoices/view', ViewInvoice::class);
        $group->get('/invoices/delete', DeleteInvoice::class);*/

        $group->get('/supplies', Supplies::class);
        $group->map(['GET', 'POST'], '/supplies/create', CreateSupply::class);
        $group->map(['GET', 'POST'], '/supplies/update', UpdateSupply::class);

        $group->get('/suppliers', Suppliers::class);
        $group->map(['GET', 'POST'], '/suppliers/update', UpdateSupplier::class);

        $group->get('/shopping-lists', ShoppingLists::class);
        $group->map(['GET', 'POST'], '/shopping-lists/create', CreateShoppingList::class);
        $group->map(['GET', 'POST'], '/shopping-lists/update', UpdateShoppingList::class);

        $group->map(['GET', 'POST'], '/shopping-list', MakeShoppingList::class);

        $group->get('/recipes', Recipes::class);
        $group->map(['GET', 'POST'], '/recipes/create', CreateRecipe::class);
        $group->map(['GET', 'POST'], '/recipes/update', UpdateRecipe::class);

        /*$group->get('/po-strings', PoStrings::class);
        $group->map(['GET', 'POST'], '/po-strings/update', UpdatePoString::class);
        $group->get('/po-strings/delete', DeletePoString::class);*/

        $group->get('/report', Report::class);
        $group->get('/predict', Predict::class);

        $group->post('/graph-ql', GraphQl::class);
    })
    ->add(\Middleware\Globals::class)
    ->add(\Middleware\Authentication::class)
    ->add(\Middleware\Authorization::class)
    ->add(\Middleware\Menus::class);

    $app->group('/orders-app', function (RouteCollectorProxy $group) {
        $group->get('/', OrdersAppHomepage::class);
        $group->map(['GET', 'POST'], '/create', CreateOrder::class);
        $group->map(['GET', 'POST'], '/payment', OrderPayment::class);
        $group->get('/print-receipt', PrintOrderReceipt::class);
        $group->get('/catch-of-the-day', CatchOfTheDay::class);
        $group->map(['GET', 'POST'], '/update', UpdateOrder::class);
        $group->map(['GET', 'POST'], '/take-out', TakeOut::class);
    })
    ->add(new Authorization($container->get('Domain\Repositories\UserPermissionsRepositoryInterface')))
    ->add(new Authentication());

    $app->group('/reservations-app', function (RouteCollectorProxy $group) {
        $group->get('/', ReservationsAppHomepage::class);
        $group->map(['GET', 'POST'], '/create', CreateReservation::class);
        $group->map(['GET', 'POST'], '/update', UpdateReservation::class);
        $group->get('/print', PrintReservations::class);
        $group->get('/tabular-view', TabularView::class);
    })
    ->add(new Authorization($container->get('Domain\Repositories\UserPermissionsRepositoryInterface')))
    ->add(new Authentication());

    $app->group('/users-app', function (RouteCollectorProxy $group) {
        $group->get('/', UsersAppHomepage::class);
        $group->get('/scans', UsersAppScans::class);
        $group->get('/orders', UsersAppOrders::class);
        $group->get('/view-order', UsersAppViewOrder::class);
        $group->map(['GET', 'POST'], '/create-order', UsersAppCreateOrder::class);
        $group->map(['GET', 'POST'], '/update-pin', UsersAppUpdatePin::class);
    })
    ->add(new Authorization($container->get('Domain\Repositories\UserPermissionsRepositoryInterface')))
    ->add(new Authentication());
};