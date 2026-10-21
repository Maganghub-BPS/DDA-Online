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
$routes->get('admin/batal_verif/(:num)', 'Admin::batal_verif/$1');
$routes->get('admin/download_template_tabel/(:num)', 'Admin::download_template_tabel/$1');
$routes->get('admin/view_tabel_dinamis/(:num)', 'Admin::view_tabel_dinamis/$1');
$routes->post('admin/upload_excel_dinamis', 'Admin::upload_excel_dinamis');
$routes->post('admin/simpan_catatan_tabel', 'Admin::simpan_catatan_tabel');
$routes->post('admin/ajax_reset_nilai_tabel', 'Admin::ajax_reset_nilai_tabel');
$routes->get('home/view_dinamis/(:num)', 'Home::view_dinamis/$1');
