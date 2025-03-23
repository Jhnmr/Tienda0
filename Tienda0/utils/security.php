<?php
/**
 * Funciones de seguridad
 */

/**
 * Sanitiza entrada de texto
 * 
 * @param string $input Texto a sanitizar
 * @return string Texto sanitizado
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Desinfecta un nombre de archivo
 * 
 * @param string $filename Nombre de archivo a desinfectar
 * @return string Nombre de archivo seguro
 */
function sanitizeFilename($filename) {
    // Eliminar caracteres no seguros
    $filename = preg_replace('/[^\w\.-]+/', '_', $filename);
    
    // Eliminar puntos consecutivos (previene subida de archivos ocultos)
    $filename = preg_replace('/\.{2,}/', '.', $filename);
    
    // Asegurarse de que no comienza con un punto
    $filename = ltrim($filename, '.');
    
    return $filename;
}

/**
 * Genera un hash seguro para una contraseña
 * 
 * @param string $password Contraseña a hashear
 * @return string Hash de la contraseña
 */
function hashPassword($password) {
    $config = require_once __DIR__ . '/../config/config.php';
    $cost = $config['security']['bcrypt_cost'] ?? 12;
    
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
}

/**
 * Verifica una contraseña contra su hash
 * 
 * @param string $password Contraseña a verificar
 * @param string $hash Hash almacenado
 * @return bool True si coincide, false si no
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Genera un ID único para una sesión
 * 
 * @return string ID de sesión
 */
function generateSessionId() {
    return bin2hex(random_bytes(32));
}

/**
 * Regenera el ID de sesión
 * 
 * @return bool True si se regeneró, false si no
 */
function regenerateSessionId() {
    return session_regenerate_id(true);
}

/**
 * Verifica la IP del usuario (para detectar secuestro de sesión)
 * 
 * @return bool True si la IP coincide, false si no
 */
function checkSessionIP() {
    if (!isset($_SESSION['ip_address'])) {
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        return true;
    }
    
    return $_SESSION['ip_address'] === $_SERVER['REMOTE_ADDR'];
}

/**
 * Verifica el agente de usuario (para detectar secuestro de sesión)
 * 
 * @return bool True si el agente coincide, false si no
 */
function checkSessionUserAgent() {
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return true;
    }
    
    return $_SESSION['user_agent'] === ($_SERVER['HTTP_USER_AGENT'] ?? '');
}

/**
 * Elimina etiquetas HTML y PHP de una cadena
 * 
 * @param string $str Cadena a limpiar
 * @return string Cadena sin etiquetas
 */
function stripTags($str) {
    return strip_tags($str);
}

/**
 * Verifica que una URL sea segura (mismo dominio)
 * 
 * @param string $url URL a verificar
 * @return bool True si es segura, false si no
 */
function isSafeUrl($url) {
    // Si es una URL relativa, es segura
    if (substr($url, 0, 1) === '/') {
        return true;
    }
    
    // Si no es una URL válida, no es segura
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    $host = $_SERVER['HTTP_HOST'];
    $urlHost = parse_url($url, PHP_URL_HOST);
    
    // Comparar dominios
    return $urlHost === $host;
}