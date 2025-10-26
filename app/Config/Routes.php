<?php
// Customer routes

$routes->get('api/customers', 'Customer::index');
$routes->post('api/customers', 'Customer::create');
$routes->put('api/customers/(:num)', 'Customer::update/$1');
$routes->delete('api/customers/(:num)', 'Customer::delete/$1');

// User routes

$routes->get('api/users', 'User::index');
$routes->post('api/users', 'User::create');
$routes->put('api/users/(:num)', 'User::update/$1');
$routes->delete('api/users/(:num)', 'User::delete/$1');

// Dashboard routes
$routes->get('api/dashboard/summary', 'Dashboard::summary');

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->post('api/login', 'Auth::login');


$routes->post('api/leads', 'Lead::create');
$routes->put('api/leads/(:num)', 'Lead::update/$1');
$routes->get('api/leads', 'Lead::index');
$routes->delete('api/leads/(:num)', 'Lead::delete/$1');
