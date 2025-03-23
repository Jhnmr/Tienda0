<?php
require_once __DIR__ . '/../utils/helpers.php';

/**
 * Limitación de peticiones (anti-flood)
 */

/**
 * Middleware para limitar la frecuencia de peticiones
 * 
 * @param string $key Clave única para identificar el tipo de límite (ej: 'login', 'api')
 * @param int $maxRequests Número máximo de peticiones permitidas
 * @param int $timeWindow Ventana de tiempo en segundos
 * @param string $redirectTo Ruta a redirigir si excede el límite
 * @return void
 */
function rateLimitMiddleware($key = 'default', $maxRequests = 10, $timeWindow = 60, $redirectTo = '/') {
    // Obtener IP del cliente
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Crear un identificador único para este límite
    $identifier = $key . '_' . $ip;
    
    // Inicializar historial de peticiones si no existe
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    if (!isset($_SESSION['rate_limits'][$identifier])) {
        $_SESSION['rate_limits'][$identifier] = [];
    }
    
    // Obtener el tiempo actual
    $now = time();
    
    // Filtrar peticiones antiguas (fuera de la ventana de tiempo)
    $_SESSION['rate_limits'][$identifier] = array_filter($_SESSION['rate_limits'][$identifier], function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    // Agregar petición actual
    $_SESSION['rate_limits'][$identifier][] = $now;
    
    // Verificar si excede el límite
    if (count($_SESSION['rate_limits'][$identifier]) > $maxRequests) {
        // Registrar el intento bloqueado
        error_log("Rate limit excedido para $identifier - IP: $ip");
        
        // Establecer mensaje flash
        setFlash('error', 'Ha realizado demasiadas peticiones en poco tiempo. Por favor, espere e intente de nuevo.');
        
        // Redirigir
        header('Location: ' . $redirectTo, true, 429); // 429 Too Many Requests
        exit;
    }
}

/**
 * Verifica si una IP está bloqueada por intentos excesivos
 * 
 * @param string $key Clave única para identificar el tipo de límite
 * @param int $maxRequests Número máximo de peticiones permitidas
 * @param int $timeWindow Ventana de tiempo en segundos
 * @return bool True si está bloqueado, false si no
 */
function isRateLimited($key = 'default', $maxRequests = 10, $timeWindow = 60) {
    // Obtener IP del cliente
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Crear un identificador único para este límite
    $identifier = $key . '_' . $ip;
    
    // Verificar si existe el historial
    if (!isset($_SESSION['rate_limits']) || !isset($_SESSION['rate_limits'][$identifier])) {
        return false;
    }
    
    // Obtener el tiempo actual
    $now = time();
    
    // Filtrar peticiones antiguas (fuera de la ventana de tiempo)
    $recentRequests = array_filter($_SESSION['rate_limits'][$identifier], function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    return count($recentRequests) >= $maxRequests;
}