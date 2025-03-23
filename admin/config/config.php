<?php
require_once __DIR__ . '/includes/middleware.php';
// Configuración general de la aplicación
return [
    // Configuración de la base de datos
    'database' => [
        'host' => 'localhost',
        'dbname' => 'tienda0',
        'username' => 'root',
        'password' => 'admin',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    
    // Configuración de la aplicación
    'app' => [
        'name' => 'Sistema de Gestión',
        'url' => 'http://localhost/tienda0',
        'version' => '1.0.0',
        'debug' => true,
    ],
    
    // Configuración de seguridad
    'security' => [
        'session_timeout' => 3600, // 1 hora en segundos
        'bcrypt_cost' => 12, // Costo de hashing bcrypt (valores entre 10-14 recomendados)
        'token_lifetime' => 86400, // 24 horas en segundos
    ]
];