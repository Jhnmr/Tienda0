<?php
/**
 * Middleware de autenticación
 */

/**
 * Verifica si el usuario está autenticado
 * 
 * @return bool True si está autenticado, false si no
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Middleware de autenticación
 * Verifica si el usuario está autenticado, caso contrario lo redirige al login
 * 
 * @param string $redirectTo Ruta a redirigir si no está autenticado
 */
function authMiddleware($redirectTo = '/login') {
    if (!isAuthenticated()) {
        // Intentar autenticar por cookie
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            
            require_once __DIR__ . '/../models/user.php';
            $userModel = new User();
            $result = $userModel->authenticateByToken($token);
            
            if ($result['success']) {
                // Establecer sesión
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['user_email'] = $result['user']['email'];
                $_SESSION['user_name'] = $result['user']['nombres'] . ' ' . $result['user']['apellidos'];
                $_SESSION['user_roles'] = array_column($result['user']['roles'], 'id');
                $_SESSION['user_permissions'] = array_column($result['user']['permissions'], 'codigo');
                
                return; // Usuario autenticado por token
            }
        }
        
        // No autenticado, redirigir al login
        setFlashMessage('error', 'Debe iniciar sesión para acceder a esta página.');
        redirect($redirectTo);
        exit;
    }
}

/**
 * Verifica si un usuario tiene un rol específico
 * 
 * @param int|array $roles ID o array de IDs de roles a verificar
 * @return bool True si tiene el rol, false si no
 */
function userHasRole($roles) {
    if (!isAuthenticated()) {
        return false;
    }
    
    // Si no se pasan roles para verificar, consideramos que cualquier usuario autenticado tiene acceso
    if (empty($roles)) {
        return true;
    }
    
    // Convertimos a array si es un solo rol
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    // Verificamos si el usuario tiene alguno de los roles requeridos
    $userRoles = $_SESSION['user_roles'] ?? [];
    
    return count(array_intersect($roles, $userRoles)) > 0;
}