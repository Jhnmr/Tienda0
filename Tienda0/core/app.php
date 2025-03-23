<?php

/**
 * Clase principal de la aplicación
 */
class app
{
    private $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->registerRoutes();
    }

    /**
     * Registra todas las rutas de la aplicación
     */
    private function registerRoutes()
    {
        // Rutas de autenticación
        $this->router->add('', 'dashboardcontroller@index');
        $this->router->add('login', 'authcontroller@login');
        $this->router->add('logout', 'authcontroller@logout');
        $this->router->add('register', 'authcontroller@register');
        $this->router->add('profile', 'authcontroller@profile');
        $this->router->add('forgotpassword', 'authcontroller@forgotpassword');
        $this->router->add('changepassword', 'authcontroller@changepassword');

        // Rutas de roles
        $this->router->add('admin/roles', 'rolecontroller@index');
        $this->router->add('admin/roles/create', 'rolecontroller@create');
        $this->router->add('admin/roles/edit/(\d+)', 'rolecontroller@edit');
        $this->router->add('admin/roles/delete/(\d+)', 'rolecontroller@delete');

        // Rutas de usuarios
        $this->router->add('admin/users', 'usercontroller@index');
        $this->router->add('admin/users/create', 'usercontroller@create');
        $this->router->add('admin/users/edit/(\d+)', 'usercontroller@edit');
        $this->router->add('admin/users/delete/(\d+)', 'usercontroller@delete');

        // Rutas de productos
        $this->router->add('admin/products', 'productcontroller@index');
        $this->router->add('admin/products/create', 'productcontroller@create');
        $this->router->add('admin/products/edit/(\d+)', 'productcontroller@edit');
        $this->router->add('admin/products/delete/(\d+)', 'productcontroller@delete');

        // Rutas de categorías
        $this->router->add('admin/categories', 'categorycontroller@index');
        $this->router->add('admin/categories/create', 'categorycontroller@create');
        $this->router->add('admin/categories/edit/(\d+)', 'categorycontroller@edit');
        $this->router->add('admin/categories/delete/(\d+)', 'categorycontroller@delete');
    }

    /**
     * Ejecuta la aplicación
     */
    public function run()
    {
        // Obtener la ruta solicitada
        $requestUri = $_SERVER['REQUEST_URI'];
        $basePath = dirname($_SERVER['SCRIPT_NAME']);

        // Eliminar la ruta base
        $path = substr($requestUri, strlen($basePath));

        // Eliminar parámetros de consulta
        $path = parse_url($path, PHP_URL_PATH);

        // Eliminar barra inicial
        $route = ltrim($path, '/');

        // Manejar la ruta
        $this->router->dispatch($route);
    }
}
