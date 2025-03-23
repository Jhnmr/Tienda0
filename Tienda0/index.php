<?php
/**
 * Sistema de Gestión
 * Punto de entrada único para toda la aplicación
 */

// Mostrar errores solo en desarrollo
ini_set('display_errors', 0);
if (file_exists(__DIR__ . '/config/config.php')) {
    $config = require __DIR__ . '/config/config.php';
    if (isset($config['app']['debug']) && $config['app']['debug']) {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    }
}

// Iniciar sesión segura
if (session_status() === PHP_SESSION_NONE) {
    $cookie_params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookie_params['lifetime'],
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_name('tienda_session');
    session_start();
}

// Incluir archivos principales
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/core/router.php';
require_once __DIR__ . '/core/app.php';

// Iniciar la aplicación
$app = new App();
$app->run();