<?php
/**
 * Protección contra CSRF (Cross-Site Request Forgery)
 */

/**
 * Genera un token CSRF
 * 
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Valida un token CSRF
 * 
 * @param string $token Token a validar
 * @return bool True si es válido, false si no
 */
function validateCSRFToken($token) {
    if (empty($token) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Comparación en tiempo constante para evitar timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Crea un campo oculto para el token CSRF
 * 
 * @return string HTML del campo oculto
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Middleware CSRF para proteger formularios
 * 
 * @param string $redirectTo Ruta a redirigir si el token es inválido
 */
function csrfMiddleware($redirectTo = null) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Error de seguridad. Por favor, intente de nuevo.');
            
            // Si no se proporciona una ruta específica, redirigir a la misma página
            if ($redirectTo === null) {
                $redirectTo = $_SERVER['REQUEST_URI'];
            }
            
            redirect($redirectTo);
            exit;
        }
    }
}