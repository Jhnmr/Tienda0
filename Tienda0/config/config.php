<?php
/**
 * Configuración principal de la aplicación
 */

// Prevenir acceso directo al archivo
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

return [
    // Información general
    'app_name'       => 'Sistema de Gestión de Tienda Online',
    'app_version'    => '1.0.0',
    
    // Entorno de la aplicación (development, testing, production)
    'environment'    => 'development',
    
    // Configuración de URL
    'base_url'       => 'http://localhost/tienda0',
    'index_page'     => '', // Dejar vacío si se utiliza .htaccess para urls amigables
    
    // Zona horaria predeterminada
    'timezone'       => 'America/Mexico_City',
    
    // Configuración de sesión
    'session' => [
        'name'           => 'tienda_session',
        'lifetime'       => 7200, // 2 horas en segundos
        'path'           => '/',
        'domain'         => '',
        'secure'         => false, // true en producción con HTTPS
        'httponly'       => true,
        'samesite'       => 'Lax', // Strict, Lax, o None (con Secure)
        'save_path'      => null, // null para usar el predeterminado
    ],
    
    // Configuración de cookies
    'cookie' => [
        'prefix'         => 'tienda_',
        'expiry'         => 86400, // 1 día en segundos
        'path'           => '/',
        'domain'         => '',
        'secure'         => false, // true en producción con HTTPS
        'httponly'       => true,
        'samesite'       => 'Lax', // Strict, Lax, o None (con Secure)
    ],
    
    // Configuración de seguridad
    'security' => [
        'csrf_token_name'  => 'csrf_token',
        'csrf_cookie_name' => 'csrf_cookie',
        'csrf_expire'      => 7200, // 2 horas en segundos
        'csrf_regenerate'  => true, // Regenerar token en cada solicitud
        'encryption_key'   => 'SuClaveDeEncriptacionMuySegura',
        
        // Configuración de contraseñas
        'password_algorithm' => PASSWORD_ARGON2ID, // PASSWORD_BCRYPT como alternativa
        'password_options'   => [
            'memory_cost' => 2048,
            'time_cost'   => 4,
            'threads'     => 3,
        ],
    ],
    
    // Configuración de archivos subidos
    'uploads' => [
        'allowed_types'  => 'jpg|jpeg|png|gif|pdf|docx|xlsx|csv',
        'max_size'       => 5120, // Tamaño máximo en KB (5MB)
        'path'           => 'public/uploads/',
    ],
    
    // Configuración de correo
    'mail' => [
        'protocol'      => 'smtp',
        'host'          => 'smtp.example.com',
        'port'          => 587,
        'username'      => 'user@example.com',
        'password'      => 'password',
        'encryption'    => 'tls',
        'from_email'    => 'no-reply@example.com',
        'from_name'     => 'Sistema de Tienda Online',
    ],
    
    // Configuración de log
    'log' => [
        'threshold'     => 1, // 0: Desactivado, 1: Error, 2: Debug, 3: Info, 4: Todo
        'path'          => 'logs/',
        'date_format'   => 'Y-m-d H:i:s',
    ],
    
    // Configuración de caché
    'cache' => [
        'driver'        => 'file', // file, redis, memcached
        'path'          => 'cache/',
        'prefix'        => 'tienda_cache_',
        'ttl'           => 3600, // Tiempo de vida en segundos (1 hora)
    ],
    
    // Rutas predeterminadas
    'routes' => [
        'default_controller' => 'home',
        'default_method'     => 'index',
        'error_controller'   => 'error',
    ],
    
    // Configuración de idioma
    'language' => [
        'default_locale' => 'es_ES',
        'fallback_locale' => 'en_US',
    ],
    
    // Configuración de mantenimiento
    'maintenance' => [
        'enabled'       => false,
        'message'       => 'El sistema está en mantenimiento. Por favor, vuelva más tarde.',
        'allowed_ips'   => ['127.0.0.1'],
    ],
];