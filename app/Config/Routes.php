<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->setAutoRoute(true);

$routes->get('admin/login', 'Admin::login');
$routes->post('admin/do_login', 'Admin::do_login');
$routes->get('admin', 'Admin::index');
$routes->post('admin/act_toggle_publish', 'Admin::act_toggle_publish');

