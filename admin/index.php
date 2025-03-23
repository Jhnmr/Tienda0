<?php
// Controlador frontal que procesa todas las solicitudes
session_start();
require_once 'config/config.php';
require_once 'helpers/router.php';

// Obtener la ruta solicitada
$requestUri = $_SERVER['REQUEST_URI'];
$route = trim(parse_url($requestUri, PHP_URL_PATH), '/');

// Sistema de enrutamiento
$router = new Router();

// Definir rutas
$router->add('', 'HomeController@index');
$router->add('auth/login', 'AuthController@showLogin');
$router->add('auth/logout', 'AuthController@logout');
$router->add('admin/dashboard', 'DashboardController@index');
$router->add('admin/roles', 'RolesController@index');
$router->add('admin/roles/create', 'RolesController@create');
$router->add('admin/roles/edit/(\d+)', 'RolesController@edit');
// Añadir más rutas según necesites

// Despachar la ruta
$router->dispatch($route);