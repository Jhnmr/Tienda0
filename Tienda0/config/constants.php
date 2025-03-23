<?php
/**
 * Constantes del sistema
 */

// Prevenir acceso directo al archivo
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

// Paths
define('APPPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('COREPATH', APPPATH . 'core' . DIRECTORY_SEPARATOR);
define('CONTROLLERSPATH', APPPATH . 'controllers' . DIRECTORY_SEPARATOR);
define('MODELSPATH', APPPATH . 'models' . DIRECTORY_SEPARATOR);
define('VIEWSPATH', APPPATH . 'views' . DIRECTORY_SEPARATOR);
define('MIDDLEWAREPATH', APPPATH . 'middleware' . DIRECTORY_SEPARATOR);
define('SERVICESPATH', APPPATH . 'services' . DIRECTORY_SEPARATOR);
define('CONFIGPATH', APPPATH . 'config' . DIRECTORY_SEPARATOR);
define('PUBLICPATH', APPPATH . 'public' . DIRECTORY_SEPARATOR);
define('UPLOADSPATH', PUBLICPATH . 'uploads' . DIRECTORY_SEPARATOR);
define('LOGSPATH', APPPATH . 'logs' . DIRECTORY_SEPARATOR);
define('CACHEPATH', APPPATH . 'cache' . DIRECTORY_SEPARATOR);

// Estados de pedido
define('PEDIDO_NUEVO', 1);
define('PEDIDO_PROCESANDO', 2);
define('PEDIDO_ENVIADO', 3);
define('PEDIDO_ENTREGADO', 4);
define('PEDIDO_CANCELADO', 5);
define('PEDIDO_DEVUELTO', 6);

// Estados de inventario
define('INVENTARIO_DISPONIBLE', 1);
define('INVENTARIO_AGOTADO', 2);
define('INVENTARIO_RESERVADO', 3);
define('INVENTARIO_DISCONTINUADO', 4);

// Estados de usuario
define('USUARIO_ACTIVO', 1);
define('USUARIO_INACTIVO', 2);
define('USUARIO_SUSPENDIDO', 3);
define('USUARIO_ELIMINADO', 4);

// Métodos de envío
define('ENVIO_ESTANDAR', 1);
define('ENVIO_EXPRESS', 2);
define('ENVIO_RECOGIDA', 3);

// Métodos de pago
define('PAGO_TARJETA', 1);
define('PAGO_TRANSFERENCIA', 2);
define('PAGO_CONTRAENTREGA', 3);
define('PAGO_PAYPAL', 4);

// Roles predeterminados
define('ROL_ADMIN', 1);
define('ROL_GERENTE', 2);
define('ROL_CLIENTE', 3);
define('ROL_VENDEDOR', 4);
define('ROL_INVENTARIO', 5);

// Códigos de respuesta API
define('API_SUCCESS', 200);
define('API_CREATED', 201);
define('API_NO_CONTENT', 204);
define('API_BAD_REQUEST', 400);
define('API_UNAUTHORIZED', 401);
define('API_FORBIDDEN', 403);
define('API_NOT_FOUND', 404);
define('API_METHOD_NOT_ALLOWED', 405);
define('API_INTERNAL_ERROR', 500);