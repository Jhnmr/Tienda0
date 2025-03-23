<?php
/* Inicializar sesión si no está iniciada
session_start();

// Incluir archivos necesarios
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/models/product.php';
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/utils/validators.php';
require_once __DIR__ . '/includes/middleware.php';
require_once __DIR__ . '/controllers/productController.php';

// Aplicar middleware de autenticación y permisos
authMiddleware();
permissionMiddleware('product_view');

// Crear instancia del controlador y llamar al método index
$controller = new productController();
$controller->index();
class userController {}*/