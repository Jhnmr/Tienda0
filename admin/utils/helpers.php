<?php
/**
 * Archivo de funciones auxiliares para la aplicación
 */

/**
 * Redirecciona a una URL específica
 * 
 * @param string $url URL a la que redireccionar
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Genera un token CSRF para proteger formularios
 * 
 * @return string Token generado
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verifica si un token CSRF es válido
 * 
 * @param string $token Token a verificar
 * @return bool True si es válido, false si no
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    
    return true;
}

/**
 * Escapa HTML para prevenir XSS
 * 
 * @param string $str Cadena a escapar
 * @return string Cadena escapada
 */
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Verifica si un usuario está autenticado
 * 
 * @return bool True si está autenticado, false si no
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
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
    
    foreach ($roles as $role) {
        if (in_array($role, $userRoles)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Verifica si un usuario tiene un permiso específico
 * 
 * @param string|array $permissions Código o array de códigos de permisos a verificar
 * @return bool True si tiene el permiso, false si no
 */
function userHasPermission($permissions) {
    if (!isAuthenticated()) {
        return false;
    }
    
    // Si no se pasan permisos para verificar, consideramos que cualquier usuario autenticado tiene acceso
    if (empty($permissions)) {
        return true;
    }
    
    // Convertimos a array si es un solo permiso
    if (!is_array($permissions)) {
        $permissions = [$permissions];
    }
    
    // Verificamos si el usuario tiene alguno de los permisos requeridos
    $userPermissions = $_SESSION['user_permissions'] ?? [];
    
    foreach ($permissions as $permission) {
        if (in_array($permission, $userPermissions)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Obtiene la fecha y hora actual formateada
 * 
 * @param string $format Formato de fecha (por defecto Y-m-d H:i:s)
 * @return string Fecha formateada
 */
function getCurrentDateTime($format = 'Y-m-d H:i:s') {
    return date($format);
}

/**
 * Genera un slug a partir de un string
 * 
 * @param string $text Texto a convertir en slug
 * @return string Slug generado
 */
function slugify($text) {
    // Reemplaza caracteres no alfanuméricos por guiones
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    
    // Elimina caracteres no deseados
    $text = preg_replace('~[^-\w]+~', '', $text);
    
    // Trim
    $text = trim($text, '-');
    
    // Elimina guiones duplicados
    $text = preg_replace('~-+~', '-', $text);
    
    // Lowercase
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

/**
 * Genera un token aleatorio seguro
 * 
 * @param int $length Longitud del token (por defecto 32)
 * @return string Token generado
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Comprueba si la solicitud actual es AJAX
 * 
 * @return bool True si es AJAX, false si no
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}