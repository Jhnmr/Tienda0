<?php
/**
 * Middleware para control de acceso y otras verificaciones
 */

/**
 * Middleware de autenticación
 * Verifica si el usuario está autenticado, caso contrario lo redirige al login
 * 
 * @param string $redirectTo Ruta a redirigir si no está autenticado
 * @return void
 */
function authMiddleware($redirectTo = '/login.php') {
    if (!isAuthenticated()) {
        $_SESSION['error_message'] = 'Debe iniciar sesión para acceder a esta página.';
        redirect($redirectTo);
    }
}

/**
 * Middleware de roles
 * Verifica si el usuario tiene alguno de los roles especificados
 * 
 * @param array|int $roles ID o array de IDs de roles permitidos
 * @param string $redirectTo Ruta a redirigir si no tiene permiso
 * @return void
 */
function roleMiddleware($roles, $redirectTo = '/dashboard.php') {
    // Primero verificamos autenticación
    authMiddleware();
    
    // Luego verificamos rol
    if (!userHasRole($roles)) {
        $_SESSION['error_message'] = 'No tiene los privilegios necesarios para acceder a esta sección.';
        redirect($redirectTo);
    }
}

/**
 * Middleware de permisos
 * Verifica si el usuario tiene alguno de los permisos especificados
 * 
 * @param array|string $permissions Código o array de códigos de permisos permitidos
 * @param string $redirectTo Ruta a redirigir si no tiene permiso
 * @return void
 */
function permissionMiddleware($permissions, $redirectTo = '/dashboard.php') {
    // Primero verificamos autenticación
    authMiddleware();
    
    // Luego verificamos permiso
    if (!userHasPermission($permissions)) {
        $_SESSION['error_message'] = 'No tiene los permisos necesarios para acceder a esta sección.';
        redirect($redirectTo);
    }
}

/**
 * Middleware para verificar si el usuario está activo
 * 
 * @param string $redirectTo Ruta a redirigir si no está activo
 * @return void
 */
function activeUserMiddleware($redirectTo = '/account-suspended.php') {
    // Primero verificamos autenticación
    authMiddleware();
    
    // Verificar si el usuario está activo (requiere consulta a la base de datos)
    // Esta es una implementación de ejemplo, ajustar según la estructura real
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT verificado FROM usuario WHERE id_usuario = :id_usuario");
    $stmt->execute([':id_usuario' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['verificado']) {
        // Cerrar sesión del usuario
        $_SESSION = [];
        session_destroy();
        
        $_SESSION['error_message'] = 'Su cuenta ha sido suspendida o no ha sido verificada.';
        redirect($redirectTo);
    }
}

/**
 * Middleware para verificar token CSRF en peticiones POST
 * 
 * @param string $redirectTo Ruta a redirigir si el token es inválido
 * @return void
 */
function csrfMiddleware($redirectTo = null) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
            
            // Si no se proporciona una ruta específica, redirigir a la misma página
            if ($redirectTo === null) {
                $redirectTo = $_SERVER['REQUEST_URI'];
            }
            
            redirect($redirectTo);
        }
    }
}

/**
 * Middleware para limitar la frecuencia de peticiones (anti-flood)
 * 
 * @param int $maxRequests Número máximo de peticiones permitidas
 * @param int $timeWindow Ventana de tiempo en segundos
 * @param string $redirectTo Ruta a redirigir si excede el límite
 * @return void
 */
function rateLimitMiddleware($maxRequests = 10, $timeWindow = 60, $redirectTo = '/too-many-requests.php') {
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
        $_SESSION['error_message'] = 'Ha realizado demasiadas peticiones en poco tiempo. Por favor, espere e intente de nuevo.';
        redirect($redirectTo);
    }
}