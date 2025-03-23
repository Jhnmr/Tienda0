<?php
/**
 * Ejecutador de seeders
 * Este script permite inicializar la base de datos con datos iniciales
 */

// Incluir archivos de seeders
require_once __DIR__ . '/seeders/permission_seeder.php';

// Función para ejecutar seeders
function runSeeders() {
    echo "=== Iniciando seeders ===\n";
    
    // Ejecutar seeder de permisos
    echo "\nEjecutando seeder de permisos...\n";
    $permissionSeeder = new PermissionSeeder();
    $permissionSeeder->run();
    
    // Aquí se pueden agregar más seeders en el futuro
    
    echo "\n=== Seeders completados ===\n";
}

// Verificación de seguridad para evitar ejecución accidental desde el navegador
if (php_sapi_name() == 'cli') {
    runSeeders();
} else {
    die("Este script debe ejecutarse desde la línea de comandos.");
}