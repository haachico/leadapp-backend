
<?php
// Customer routes
$routes->get('api/customers', 'Customer::index');
$routes->post('api/customers', 'Customer::create');
$routes->put('api/customers/(:num)', 'Customer::update/$1');

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->post('api/login', 'Auth::login');

$routes->post('api/leads', 'Lead::create');

$routes->put('api/leads/(:num)', 'Lead::update/$1');

$routes->get('api/leads', 'Lead::index');
