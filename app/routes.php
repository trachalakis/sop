<?php

declare(strict_types=1);

use Application\Actions\Homepage;
use Application\Actions\Login;
use Application\Actions\Logout;
use Application\Actions\Sdp;
use Application\Actions\PrinterStatus;
use Application\Actions\Admin\CreateMenu;
use Application\Actions\Admin\DeleteMenu;
use Application\Actions\Admin\CopyMenuSection;
use Application\Actions\Admin\CreateMenuItem;
use Application\Actions\Admin\CreateMenuSection;
use Application\Actions\Admin\CreatePrinter;
use Application\Actions\Admin\CreatePrintJob;
use Application\Actions\Admin\CreateScan;
use Application\Actions\Admin\CreateSupply;
use Application\Actions\Admin\CreateTable;
use Application\Actions\Admin\CreateUser;
use Application\Actions\Admin\DeleteMenuItem;
use Application\Actions\Admin\DeleteMenuSection;
use Application\Actions\Admin\DeleteOrder;
use Application\Actions\Admin\DeletePrinter;
use Application\Actions\Admin\DeletePrintJob;
use Application\Actions\Admin\DeleteTable;
use Application\Actions\Admin\DeleteScan;
use Application\Actions\Admin\DeleteSupply;
use Application\Actions\Admin\GraphQl;
use Application\Actions\Admin\Homepage as AdminHomepage;
use Application\Actions\Admin\Languages;
use Application\Actions\Admin\Menu as AdminMenu;
use Application\Actions\Admin\Menus as AdminMenus;
use Application\Actions\Admin\MenuItemRecipe;
use Application\Actions\Admin\MenuItemStatistics;
use Application\Actions\Admin\MenuSectionStatistics;
use Application\Actions\Admin\OpenOrder;
use Application\Actions\Admin\Predict;
use Application\Actions\Admin\Printers;
use Application\Actions\Admin\PrintJobs;
use Application\Actions\Admin\PrintMenu;
use Application\Actions\Admin\Report;
use Application\Actions\Admin\Scans;
use Application\Actions\Admin\PrintSupplies;
use Application\Actions\Admin\SaveShoppingList;
use Application\Actions\Admin\Supplies;
use Application\Actions\Admin\SortMenuItems;
use Application\Actions\Admin\SortMenuSections;
use Application\Actions\Admin\SortTables;
use Application\Actions\Admin\Tables;
use Application\Actions\Admin\ToggleArchive;
use Application\Actions\Admin\ToggleMenuItem;
use Application\Actions\Admin\ToggleLanguage;
use Application\Actions\Admin\UpdateMenu;
use Application\Actions\Admin\UpdateMenuItem;
use Application\Actions\Admin\UpdateMenuSection;
use Application\Actions\Admin\UpdatePrinter;
use Application\Actions\Admin\UpdatePrintJob;
use Application\Actions\Admin\UpdateScan;
use Application\Actions\Admin\UpdateSupply;
use Application\Actions\Admin\UpdateTable;
use Application\Actions\Admin\UpdateUser;
use Application\Actions\Admin\Users;
use Application\Actions\Admin\UserScans;
use Application\Actions\Admin\UserOrders;
use Application\Actions\Admin\ViewOrder;
use Application\Actions\OrdersApp\Homepage as OrdersAppHomepage;
use Application\Actions\OrdersApp\CatchOfTheDay;
use Application\Actions\OrdersApp\CreateOrder;
use Application\Actions\OrdersApp\OrderPayment;
use Application\Actions\OrdersApp\PrintOrderReceipt;
use Application\Actions\OrdersApp\UpdateOrder;
use Application\Actions\OrdersApp\TakeOut;
use Application\Actions\ReservationsApp\Homepage as ReservationsAppHomepage;
use Application\Actions\ReservationsApp\CreateReservation;
use Application\Actions\ReservationsApp\UpdateReservation;
use Application\Actions\ReservationsApp\HomepageAlt as ReservationsAppHomepageAlt;
use Application\Actions\ReservationsApp\AssignReservationTables;
use Application\Actions\ReservationsApp\TabularView;
use Application\Actions\UsersApp\Homepage as UsersAppHomepage;
use Application\Actions\UsersApp\Scans as UsersAppScans;
use Application\Actions\UsersApp\Orders as UsersAppOrders;
use Application\Actions\UsersApp\CreateOrder as UsersAppCreateOrder;
use Application\Actions\UsersApp\ViewOrder as UsersAppViewOrder;
use Application\Actions\UsersApp\UpdatePin as UsersAppUpdatePin;
use Application\Actions\UsersApp\Clock;
use Domain\Repositories\UserPermissionsRepository;
use Middleware\Authentication;
use Middleware\Authorization;
use Middleware\Globals;
use Middleware\Menus;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app, $container) {
    $app->get('/[{language:[a-z]{2}}]', Homepage::class);

    $app->map(['GET', 'POST'], '/login', Login::class);
    $app->get('/logout', Logout::class);

    $app->map(['GET', 'POST'], '/printers/sdp', Sdp::class);
    $app->map(['GET', 'POST'], '/printers/status', PrinterStatus::class);

    $app->group('/admin', function (RouteCollectorProxy $group) {
        $group->get('/', AdminHomepage::class);

        $group->get('/orders/open', OpenOrder::class);
        $group->get('/orders/view', ViewOrder::class);
        $group->get('/orders/delete', DeleteOrder::class);

        $group->get('/tables', Tables::class);
        $group->map(['GET', 'POST'], '/tables/create', CreateTable::class);
        $group->map(['GET', 'POST'], '/tables/update', UpdateTable::class);
        $group->get('/tables/delete', DeleteTable::class);
        $group->post('/tables/sort', SortTables::class);

        $group->get('/menus', AdminMenus::class);
        $group->map(['GET', 'POST'], '/menus/create', CreateMenu::class);
        $group->map(['GET', 'POST'], '/menus/update', UpdateMenu::class);
        $group->get('/menus/delete', DeleteMenu::class);
        $group->get('/menu', AdminMenu::class);
        $group->get('/menu/print', PrintMenu::class);
        
        $group->map(['GET', 'POST'], '/menu-sections/create', CreateMenuSection::class);
        $group->map(['GET', 'POST'], '/menu-sections/update', UpdateMenuSection::class);
        $group->get('/menu-sections/statistics', MenuSectionStatistics::class);
        $group->post('/menu-sections/sort', SortMenuSections::class);
        $group->get('/menu-sections/copy', CopyMenuSection::class);
        $group->get('/menu-sections/delete', DeleteMenuSection::class);
        
        $group->map(['GET', 'POST'], '/menu-items/create', CreateMenuItem::class);
        $group->map(['GET', 'POST'], '/menu-items/update', UpdateMenuItem::class);
        $group->get('/menu-items/toggle-archive', ToggleArchive::class);
        $group->map(['GET', 'POST'], '/menu-items/recipe', MenuItemRecipe::class);
        $group->get('/menu-items/statistics', MenuItemStatistics::class);
        $group->get('/menu-items/toggle', ToggleMenuItem::class);
        $group->post('/menu-items/sort', SortMenuItems::class);
        $group->get('/menu-items/delete', DeleteMenuItem::class);
        
        $group->get('/users', Users::class);
        $group->map(['GET', 'POST'], '/users/create', CreateUser::class);
        $group->map(['GET', 'POST'], '/users/update', UpdateUser::class);
        $group->get('/users/orders', UserOrders::class);

        $group->get('/scans', Scans::class);
        $group->map(['GET', 'POST'], '/scans/create', CreateScan::class);
        $group->map(['GET', 'POST'], '/scans/update', UpdateScan::class);
        $group->get('/scans/delete', DeleteScan::class);
        $group->get('/scans/user', UserScans::class);

        $group->get('/supplies', Supplies::class);
        $group->get('/supplies/print', PrintSupplies::class);
        $group->post('/shopping-lists/save', SaveShoppingList::class);
        $group->map(['GET', 'POST'], '/supplies/create', CreateSupply::class);
        $group->map(['GET', 'POST'], '/supplies/update', UpdateSupply::class);
        $group->map(['GET', 'POST'], '/supplies/delete', DeleteSupply::class);

        $group->get('/languages', Languages::class);
        $group->get('/languages/toggle', ToggleLanguage::class);

        $group->get('/printers', Printers::class);
        $group->map(['GET', 'POST'], '/printers/create', CreatePrinter::class);
        $group->map(['GET', 'POST'], '/printers/update', UpdatePrinter::class);
        $group->get('/printers/delete', DeletePrinter::class);
        $group->get('/print-jobs', PrintJobs::class);
        $group->post('/print-jobs/create', CreatePrintJob::class);
        $group->map(['GET', 'POST'], '/print-jobs/update', UpdatePrintJob::class);
        $group->map(['GET', 'POST'], '/print-jobs/delete', DeletePrintJob::class);

        $group->get('/report', Report::class);
        $group->get('/predict', Predict::class);

        $group->post('/graph-ql', GraphQl::class);
    })
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
    ->add(new Authorization($container->get(UserPermissionsRepository::class)))
    ->add(new Authentication());

    $app->group('/reservations-app', function (RouteCollectorProxy $group) {
        $group->get('/', ReservationsAppHomepage::class);
        $group->map(['GET', 'POST'], '/create', CreateReservation::class);
        $group->map(['GET', 'POST'], '/update', UpdateReservation::class);
        $group->get('/tabular-view', TabularView::class);
        $group->get('/homepage-alt', ReservationsAppHomepageAlt::class);
        $group->post('/assign-tables', AssignReservationTables::class);
    })
    ->add(new Authorization($container->get(UserPermissionsRepository::class)))
    ->add(new Authentication());

    $app->group('/users-app', function (RouteCollectorProxy $group) {
        $group->get('/', UsersAppHomepage::class);
        $group->get('/scans', UsersAppScans::class);
        $group->get('/orders', UsersAppOrders::class);
        $group->get('/view-order', UsersAppViewOrder::class);
        $group->map(['GET', 'POST'], '/create-order', UsersAppCreateOrder::class);
        $group->map(['GET', 'POST'], '/update-pin', UsersAppUpdatePin::class);
        $group->get('/clock', Clock::class);
    })
    ->add(new Authorization($container->get(UserPermissionsRepository::class)))
    ->add(new Authentication());

    $app->add(\Middleware\Globals::class);
};