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

// Cargar archivos de sistema
require_once __DIR__ . '/core/router.php';
require_once __DIR__ . '/core/app.php';

// Autocargador de clases básico (complementará a Composer cuando esté configurado)
spl_autoload_register(function ($className) {
    // Convertir namespace separadores a separadores de directorio
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    
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

// Incluir archivo de utilidades si existe
if (file_exists(__DIR__ . '/utils/helpers.php')) {
    require_once __DIR__ . '/utils/helpers.php';
}

// Crear instancia de la aplicación
$app = new App();

// Configurar rutas
require_once __DIR__ . '/config/routes.php';

// Ejecutar la aplicación
$app->run();