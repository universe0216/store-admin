<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('purchases', 'Purchases::index');
$routes->get('purchases/create', 'Purchases::create');
$routes->get('sells', 'Sells::index');
$routes->get('sells/create', 'Sells::create');
$routes->get('stock', 'Stock::index');
$routes->get('inventory', 'Inventory::index');
$routes->get('suppliers', 'Suppliers::index');
$routes->get('categories', 'Categories::index');
$routes->get('warehouses', 'Warehouses::index');
$routes->get('currencies', 'Currencies::index');

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
    $routes->get('warehouses', 'Api\Warehouses::index');
    $routes->get('warehouses/(:num)', 'Api\Warehouses::show/$1');
    $routes->post('warehouses', 'Api\Warehouses::create');
    $routes->put('warehouses/(:num)', 'Api\Warehouses::update/$1');
    $routes->delete('warehouses/(:num)', 'Api\Warehouses::delete/$1');
    $routes->get('currencies', 'Api\Currencies::index');
    $routes->get('currencies/(:segment)', 'Api\Currencies::show/$1');
    $routes->post('currencies', 'Api\Currencies::create');
    $routes->put('currencies/(:segment)', 'Api\Currencies::update/$1');
    $routes->delete('currencies/(:segment)', 'Api\Currencies::delete/$1');
    $routes->get('products', 'Api\Purchases::products');
    $routes->post('products', 'Api\Purchases::createProduct');
    $routes->get('product-variants', 'Api\Purchases::variants');
    $routes->get('inventory', 'Api\Purchases::inventory');
    $routes->get('stock', 'Api\Purchases::stock');
    $routes->put('stock/warehouse', 'Api\Purchases::updateVariantWarehouse');
    $routes->get('sales', 'Api\Sells::index');
    $routes->get('sales/(:num)', 'Api\Sells::show/$1');
    $routes->post('sales', 'Api\Sells::create');
    $routes->get('warehouse-products', 'Api\Sells::productsByWarehouse');
});
