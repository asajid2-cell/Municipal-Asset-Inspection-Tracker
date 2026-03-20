<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('login', 'Auth::loginForm');
$routes->post('login', 'Auth::login');

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('logout', 'Auth::logout');
    $routes->get('/', 'Home::index');
    $routes->get('reports', 'Reports::index');
    $routes->get('digital-twin', 'DigitalTwin::index');
    $routes->get('capital-planning', 'CapitalPlans::index');
    $routes->get('mobile-ops', 'MobileOps::index');
    $routes->get('assets', 'Assets::index');
    $routes->get('assets/full', 'Assets::full');
    $routes->get('assets/map', 'Assets::map');
    $routes->get('assets/import-template', 'Assets::importTemplate');
    $routes->get('assets/(:num)', 'Assets::show/$1');
    $routes->get('maintenance-requests', 'MaintenanceRequests::index');
    $routes->get('notifications', 'Notifications::index');
    $routes->get('audit-log', 'ActivityLogs::index');
    $routes->get('exports', 'Exports::index');
    $routes->get('attachments/(:num)/download', 'Attachments::download/$1');

    $routes->group('', ['filter' => 'role:admin,operations_coordinator,inspector,department_manager'], static function (RouteCollection $routes): void {
        $routes->get('assets/new', 'Assets::createForm');
        $routes->post('assets', 'Assets::create');
        $routes->post('assets/import', 'Assets::import');
        $routes->post('assets/open-data-sync', 'Assets::syncOpenData');
        $routes->get('assets/(:num)/inspections/new', 'AssetInspections::createForm/$1');
        $routes->post('assets/(:num)/inspections', 'AssetInspections::create/$1');
        $routes->get('assets/(:num)/maintenance-requests/new', 'MaintenanceRequests::createForm/$1');
        $routes->post('assets/(:num)/maintenance-requests', 'MaintenanceRequests::create/$1');
        $routes->get('assets/(:num)/edit', 'Assets::editForm/$1');
        $routes->post('assets/(:num)', 'Assets::update/$1');
        $routes->post('assets/(:num)/archive', 'Assets::archive/$1');
        $routes->get('maintenance-requests/(:num)/edit', 'MaintenanceRequests::editForm/$1');
        $routes->post('maintenance-requests/(:num)', 'MaintenanceRequests::update/$1');
        $routes->post('notifications/overdue-reminders', 'Notifications::sendOverdueReminders');
        $routes->post('exports/assets', 'Exports::createAssetExport');
        $routes->post('capital-planning', 'CapitalPlans::create');
        $routes->post('mobile-ops/packets', 'MobileOps::createPacket');
        $routes->post('mobile-ops/conflicts', 'MobileOps::recordConflict');
        $routes->get('exports/(:num)/download', 'Exports::download/$1');
    });

    $routes->group('', ['filter' => 'role:admin'], static function (RouteCollection $routes): void {
        $routes->get('admin', 'Admin::index');
    });
});

$routes->group('api', ['filter' => 'api-auth'], static function (RouteCollection $routes): void {
    $routes->get('assets', 'Api\Assets::index');
    $routes->get('assets/map', 'Api\Assets::map');
    $routes->get('assets/(:num)', 'Api\Assets::show/$1');
    $routes->get('assets/(:num)/inspections', 'Api\Assets::inspections/$1');
});
