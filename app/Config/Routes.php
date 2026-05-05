<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('purchases', 'Purchases::index');
$routes->get('purchases/create', 'Purchases::create');
$routes->get('suppliers', 'Suppliers::index');
$routes->get('categories', 'Categories::index');

$routes->group('api', static function ($routes): void {
    $routes->get('purchases', 'Api\Purchases::index');
    $routes->get('purchases/(:num)', 'Api\Purchases::show/$1');
    $routes->post('purchases', 'Api\Purchases::create');
    $routes->get('suppliers', 'Api\Suppliers::index');
    $routes->get('suppliers/(:num)', 'Api\Suppliers::show/$1');
    $routes->post('suppliers', 'Api\Suppliers::create');
    $routes->put('suppliers/(:num)', 'Api\Suppliers::update/$1');
    $routes->delete('suppliers/(:num)', 'Api\Suppliers::delete/$1');
    $routes->get('categories', 'Api\Categories::index');
    $routes->get('categories/(:num)', 'Api\Categories::show/$1');
    $routes->post('categories', 'Api\Categories::create');
    $routes->put('categories/(:num)', 'Api\Categories::update/$1');
    $routes->delete('categories/(:num)', 'Api\Categories::delete/$1');
    $routes->get('products', 'Api\Purchases::products');
    $routes->post('products', 'Api\Purchases::createProduct');
    $routes->get('product-variants', 'Api\Purchases::variants');
});
