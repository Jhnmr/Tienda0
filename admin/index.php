<?php
// Iniciar sesión
session_start();

// Cargar configuraciones
require_once 'config/config.php';

// Cargar helpers y clases necesarias
require_once 'helpers/Router.php';

// Cargar middlewares
require_once 'includes/middleware.php';
require_once 'includes/auth_functions.php';

// Crear instancia del router
$router = new Router();

// Definir rutas

// Rutas públicas
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->get('/reset-password', 'AuthController@showResetPassword');
$router->post('/reset-password', 'AuthController@resetPassword');

// Ruta por defecto - redirige según si está autenticado
$router->get('/', function() {
    if (isAuthenticated()) {
        redirect('/admin/dashboard');
    } else {
        redirect('/login');
    }
});

// Rutas de administración
// Dashboard
$router->get('/admin/dashboard', 'DashboardController@index', ['authMiddleware']);

// Roles
$router->get('/admin/roles', 'RolesController@index', ['permissionMiddleware', ['roles_view']]);
$router->get('/admin/roles/create', 'RolesController@create', ['permissionMiddleware', ['roles_create']]);
$router->post('/admin/roles/store', 'RolesController@store', ['permissionMiddleware', ['roles_create'], 'csrfMiddleware']);
$router->get('/admin/roles/edit/:id', 'RolesController@edit', ['permissionMiddleware', ['roles_edit']]);
$router->post('/admin/roles/update/:id', 'RolesController@update', ['permissionMiddleware', ['roles_edit'], 'csrfMiddleware']);
$router->post('/admin/roles/delete/:id', 'RolesController@delete', ['permissionMiddleware', ['roles_delete'], 'csrfMiddleware']);

// Usuarios
$router->get('/admin/users', 'UsersController@index', ['permissionMiddleware', ['users_view']]);
$router->get('/admin/users/create', 'UsersController@create', ['permissionMiddleware', ['users_create']]);
$router->post('/admin/users/store', 'UsersController@store', ['permissionMiddleware', ['users_create'], 'csrfMiddleware']);
$router->get('/admin/users/edit/:id', 'UsersController@edit', ['permissionMiddleware', ['users_edit']]);
$router->post('/admin/users/update/:id', 'UsersController@update', ['permissionMiddleware', ['users_edit'], 'csrfMiddleware']);
$router->post('/admin/users/delete/:id', 'UsersController@delete', ['permissionMiddleware', ['users_delete'], 'csrfMiddleware']);

// Productos
$router->get('/admin/products', 'ProductsController@index', ['permissionMiddleware', ['products_view']]);
// Añadir más rutas para productos...

// Página 404
$router->notFound(function() {
    header("HTTP/1.0 404 Not Found");
    include 'views/errors/404.php';
});

// Despachar la solicitud
$router->dispatch();