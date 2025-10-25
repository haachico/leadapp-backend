<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->post('api/login', 'Auth::login');

$routes->post('api/leads', 'Lead::create');

$routes->put('api/leads/(:num)', 'Lead::update/$1');
