<?php
/**
 * Configuración de rutas de la aplicación
 * 
 * Este archivo define todas las rutas disponibles en el sistema
 */

// Prevenir acceso directo al archivo
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

// Obtener instancia del router
$router = $app->getRouter();

// Middleware comunes
$authMiddleware = ['Auth'];
$adminMiddleware = ['Auth', 'AdminAuth'];
$csrfMiddleware = ['CSRF'];

// ------------------------------------------------
// Rutas públicas (sin autenticación)
// ------------------------------------------------

// Página principal
$router->get('', 'shop/HomeController@index');
$router->get('/', 'shop/HomeController@index');
$router->get('home', 'shop/HomeController@index');

// Autenticación
$router->get('login', 'AuthController@loginForm');
$router->post('login', 'AuthController@login', $csrfMiddleware);
$router->get('register', 'AuthController@registerForm');
$router->post('register', 'AuthController@register', $csrfMiddleware);
$router->get('forgot-password', 'AuthController@forgotPasswordForm');
$router->post('forgot-password', 'AuthController@forgotPassword', $csrfMiddleware);
$router->get('reset-password/:token', 'AuthController@resetPasswordForm');
$router->post('reset-password/:token', 'AuthController@resetPassword', $csrfMiddleware);
$router->get('logout', 'AuthController@logout', $authMiddleware);
$router->get('verify/:token', 'AuthController@verify');

// Catálogo de productos
$router->get('productos', 'shop/ProductController@index');
$router->get('productos/:slug', 'shop/ProductController@show');
$router->get('categorias', 'shop/CategoryController@index');
$router->get('categorias/:slug', 'shop/CategoryController@show');
$router->get('buscar', 'shop/ProductController@search');

// Carrito de compras
$router->get('carrito', 'shop/CartController@index');
$router->post('carrito/agregar', 'shop/CartController@add', $csrfMiddleware);
$router->post('carrito/actualizar', 'shop/CartController@update', $csrfMiddleware);
$router->post('carrito/eliminar', 'shop/CartController@remove', $csrfMiddleware);
$router->get('carrito/vaciar', 'shop/CartController@clear');

// ------------------------------------------------
// Rutas de cliente (requieren autenticación)
// ------------------------------------------------

// Perfil y cambio de contraseña
$router->get('profile', 'AuthController@profile', $authMiddleware);
$router->post('profile', 'AuthController@profile', $authMiddleware);
$router->get('changepassword', 'AuthController@changePasswordForm', $authMiddleware);
$router->post('changepassword', 'AuthController@changePassword', $authMiddleware);

// Grupo de rutas de cuenta de usuario
$router->group('cuenta', function($router) {
   $router->get('', 'shop/AccountController@index');
   $router->get('pedidos', 'shop/AccountController@orders');
   $router->get('pedidos/:num', 'shop/AccountController@showOrder');
   $router->get('perfil', 'shop/AccountController@profile');
   $router->post('perfil', 'shop/AccountController@updateProfile');
   $router->get('direcciones', 'shop/AccountController@addresses');
   $router->post('direcciones', 'shop/AccountController@addAddress');
   $router->post('direcciones/:num/editar', 'shop/AccountController@updateAddress');
   $router->post('direcciones/:num/eliminar', 'shop/AccountController@deleteAddress');
   $router->get('wishlist', 'shop/WishlistController@index');
   $router->post('wishlist/agregar', 'shop/WishlistController@add');
   $router->post('wishlist/eliminar', 'shop/WishlistController@remove');
}, $authMiddleware);

// Proceso de checkout
$router->group('checkout', function($router) {
   $router->get('', 'shop/CheckoutController@index');
   $router->post('direccion', 'shop/CheckoutController@saveAddress');
   $router->get('envio', 'shop/CheckoutController@shipping');
   $router->post('envio', 'shop/CheckoutController@saveShipping');
   $router->get('pago', 'shop/CheckoutController@payment');
   $router->post('pago', 'shop/CheckoutController@processPayment');
   $router->get('confirmacion', 'shop/CheckoutController@confirmation');
   $router->get('gracias/:num', 'shop/CheckoutController@thankYou');
}, $authMiddleware);

// ------------------------------------------------
// Rutas de panel de administración
// ------------------------------------------------

// Grupo de rutas de administración
$router->group('admin', function($router) {
   // Dashboard
   $router->get('', 'admin/DashboardController@index');
   $router->get('dashboard', 'admin/DashboardController@index');
   
   // Usuarios
   $router->resource('usuarios', 'admin/UserController');
   
   // Roles y permisos
   $router->resource('roles', 'admin/RoleController');
   $router->get('roles/:num/permisos', 'admin/RoleController@permissions');
   $router->post('roles/:num/permisos', 'admin/RoleController@updatePermissions');
   
   // Categorías
   $router->resource('categorias', 'admin/CategoryController');
   
   // Productos
   $router->resource('productos', 'admin/ProductController');
   $router->get('productos/:num/imagenes', 'admin/ProductController@images');
   $router->post('productos/:num/imagenes', 'admin/ProductController@uploadImage');
   $router->post('productos/:num/imagenes/:num/eliminar', 'admin/ProductController@deleteImage');
   
   // Inventario
   $router->get('inventario', 'admin/InventoryController@index');
   $router->post('inventario/actualizar', 'admin/InventoryController@update');
   $router->get('inventario/movimientos', 'admin/InventoryController@movements');
   $router->get('inventario/alertas', 'admin/InventoryController@alerts');
   
   // Pedidos
   $router->resource('pedidos', 'admin/OrderController');
   $router->post('pedidos/:num/estado', 'admin/OrderController@updateStatus');
   
   // Clientes
   $router->get('clientes', 'admin/CustomerController@index');
   $router->get('clientes/:num', 'admin/CustomerController@show');
   
   // Reportes
   $router->get('reportes/ventas', 'admin/ReportController@sales');
   $router->get('reportes/productos', 'admin/ReportController@products');
   $router->get('reportes/clientes', 'admin/ReportController@customers');
   $router->get('reportes/inventario', 'admin/ReportController@inventory');
   
   // Configuración
   $router->get('configuracion', 'admin/SettingController@index');
   $router->post('configuracion', 'admin/SettingController@update');
   $router->get('configuracion/pagos', 'admin/SettingController@payments');
   $router->post('configuracion/pagos', 'admin/SettingController@updatePayments');
   $router->get('configuracion/envios', 'admin/SettingController@shipping');
   $router->post('configuracion/envios', 'admin/SettingController@updateShipping');
   
}, $adminMiddleware);

// ------------------------------------------------
// Rutas de API
// ------------------------------------------------

// Grupo de rutas de API
$router->group('api', function($router) {
   // Autenticación API
   $router->post('auth/login', 'api/AuthController@login');
   $router->post('auth/refresh', 'api/AuthController@refresh');
   
   // Productos
   $router->get('productos', 'api/ProductController@index');
   $router->get('productos/:num', 'api/ProductController@show');
   $router->get('categorias', 'api/CategoryController@index');
   $router->get('categorias/:num', 'api/CategoryController@show');
   $router->get('categorias/:num/productos', 'api/CategoryController@products');
   
   // Carrito y checkout (requieren token JWT)
   $router->group('cart', function($router) {
       $router->get('', 'api/CartController@index');
       $router->post('', 'api/CartController@add');
       $router->put(':num', 'api/CartController@update');
       $router->delete(':num', 'api/CartController@remove');
   }, ['ApiAuth']);
   
   // Pedidos (requieren token JWT)
   $router->group('pedidos', function($router) {
       $router->get('', 'api/OrderController@index');
       $router->get(':num', 'api/OrderController@show');
       $router->post('', 'api/OrderController@create');
   }, ['ApiAuth']);
});

// ------------------------------------------------
// Manejo de errores
// ------------------------------------------------

// Ruta para errores 404
$router->get('404', 'ErrorController@notFound');

// Ruta para errores 403
$router->get('403', 'ErrorController@forbidden');

// Ruta para errores 500
$router->get('500', 'ErrorController@serverError');