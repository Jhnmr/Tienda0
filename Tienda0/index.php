<?php
/**
 * Punto de entrada principal para el Sistema de Gestión de Tienda Online
 * 
 * Este archivo actúa como un controlador frontal que inicializa 
 * la aplicación y maneja todas las solicitudes entrantes.
 */

// Definir directorio base
define('BASEPATH', __DIR__);

// Prevenir acceso directo a este archivo desde navegador sin servidor
if (php_sapi_name() === 'cli-server' && 
    is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

// Configuración de errores (solo para inicialización)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar versión de PHP
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('Se requiere PHP 7.4 o superior. Versión actual: ' . PHP_VERSION);
}

// Verificar extensiones requeridas
$requiredExtensions = [
    'pdo', 'pdo_mysql', 'json', 'fileinfo', 'mbstring'
];

foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        die('Se requiere la extensión PHP: ' . $extension);
    }
}

// Establecer encoding de salida UTF-8
header('Content-Type: text/html; charset=UTF-8');

// Iniciar sesión antes de cargar cualquier otra cosa
session_start();

// Cargar constantes desde constants.php
require_once __DIR__ . '/config/constants.php';

// Cargar archivos de sistema
require_once __DIR__ . '/core/router.php';
require_once __DIR__ . '/core/app.php';

// Cargar funciones de ayuda
require_once __DIR__ . '/utils/helpers.php';


// Autocargador de clases básico
spl_autoload_register(function ($className) {
    // Convertir namespace separadores a separadores de directorio
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $className = strtolower($className);
    
    // Buscar en directorios principales
    $directories = [
        'controllers/',
        'controllers/admin/',
        'controllers/shop/',
        'models/',
        'services/',
        'middleware/',
        'core/'
    ];
    
    foreach ($directories as $directory) {
        $file = BASEPATH . DIRECTORY_SEPARATOR . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Incluir el autocargador de Composer si existe
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Configurar manejo de errores para depuración
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error = date('[Y-m-d H:i:s]') . " Error: $errstr in $errfile on line $errline";
    error_log($error, 3, LOGSPATH . '/debug.log');
    
    if (ini_get('display_errors')) {
        echo "<div style='background-color: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Error:</strong> $errstr in $errfile on line $errline";
        echo "</div>";
    }
    
    return true;
}
set_error_handler('customErrorHandler');

try {
    // Crear instancia de la aplicación
    $app = new App();

    // Cargar configuración de rutas
    require_once CONFIGPATH . 'routes.php';

    // Ejecutar la aplicación
    $app->run();
} catch (Exception $e) {
    $error = date('[Y-m-d H:i:s]') . " Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
    error_log($error, 3, LOGSPATH . '/error.log');
    
    if (ini_get('display_errors')) {
        echo "<div style='background-color: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Exception:</strong> " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
        echo "</div>";
    } else {
        // En producción, mostrar una página de error genérica
        include VIEWSPATH . 'errors/500.php';
    }
}