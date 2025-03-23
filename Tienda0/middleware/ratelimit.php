<?php
/**
 * Limitación de peticiones (anti-flood)
 */

/**
 * Middleware para limitar la frecuencia de peticiones
 * 
 * @param int $maxRequests Número máximo de peticiones permitidas
 * @param int $timeWindow Ventana de tiempo en segundos
 * @param string $redirectTo Ruta a redirigir si excede el límite
 */
function rateLimitMiddleware($maxRequests = 10, $timeWindow = 60, $redirectTo = '/') {
    // Inicializar historial de peticiones si no existe
    if (!isset($_SESSION['request_history'])) {
        $_SESSION['request_history'] = [];
    }
    
    // Obtener el tiempo actual
    $now = time();
    
    // Filtrar peticiones antiguas (fuera de la ventana de tiempo)
    $_SESSION['request_history'] = array_filter($_SESSION['request_history'], function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    // Agregar petición actual
    $_SESSION['request_history'][] = $now;
    
    // Verificar si excede el límite
    if (count($_SESSION['request_history']) > $maxRequests) {
        setFlashMessage('error', 'Ha realizado demasiadas peticiones en poco tiempo. Por favor, espere e intente de nuevo.');
        redirect($redirectTo);
        exit;
    }
}