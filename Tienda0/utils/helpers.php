<?php
/**
 * Funciones de ayuda para la aplicación
 * Este archivo contiene funciones de utilidad que se pueden
 * usar en cualquier parte de la aplicación
 */

// Prevenir acceso directo al archivo
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

/**
 * Escapar datos para prevenir XSS
 * 
 * @param string $str Cadena a escapar
 * @return string Cadena escapada
 */
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Generar un token CSRF
 * 
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        global $app;
        return $app->generateCsrfToken();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verificar si una ruta está activa
 * 
 * @param string $path Ruta a verificar
 * @return boolean
 */
function isActive($path) {
    $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return strpos($current_path, $path) !== false;
}

/**
 * Redirección segura
 * 
 * @param string $url URL a redireccionar
 * @param int $status Código de estado HTTP
 * @return void
 */
function redirect($url, $status = 302) {
    // Verificar que la URL es segura
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        // Si no es una URL completa, asumir que es una ruta interna
        $config = require CONFIGPATH . 'config.php';
        $url = rtrim($config['base_url'], '/') . '/' . ltrim($url, '/');
    }
    
    // Prevenir header injection
    $url = str_replace(["\r", "\n", "%0d", "%0a"], '', $url);
    
    header('Location: ' . $url, true, $status);
    exit;
}

/**
 * Cargar una vista
 * 
 * @param string $view Ruta de la vista
 * @param array $data Datos para pasar a la vista
 * @param boolean $return Retornar vista en lugar de imprimirla
 * @return string|void
 */
function view($view, $data = [], $return = false) {
    // Convertir la ruta de la vista a una ruta de archivo
    $viewPath = VIEWSPATH . str_replace('.', '/', $view) . '.php';
    
    // Verificar si la vista existe
    if (!file_exists($viewPath)) {
        throw new Exception("Vista no encontrada: {$view}");
    }
    
    // Extraer los datos para hacer que estén disponibles en la vista
    extract($data);
    
    // Capturar la salida
    if ($return) {
        ob_start();
    }
    
    include $viewPath;
    
    if ($return) {
        return ob_get_clean();
    }
}

/**
 * Obtener la URL base de la aplicación
 * 
 * @param string $path Ruta a agregar a la base
 * @return string URL completa
 */
function baseUrl($path = '') {
    $config = require CONFIGPATH . 'config.php';
    return rtrim($config['base_url'], '/') . '/' . ltrim($path, '/');
}

/**
 * Obtener una URL para activos (CSS, JS, imágenes)
 * 
 * @param string $path Ruta del activo
 * @return string URL completa
 */
function assetUrl($path) {
    return baseUrl('public/assets/' . ltrim($path, '/'));
}

/**
 * Generar un slug a partir de un texto
 * 
 * @param string $text Texto a convertir
 * @return string Slug generado
 */
function slugify($text) {
    // Reemplazar caracteres no alfanuméricos con guiones
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    
    // Transliterar
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    
    // Eliminar caracteres no deseados
    $text = preg_replace('~[^-\w]+~', '', $text);
    
    // Eliminar guiones duplicados
    $text = preg_replace('~-+~', '-', $text);
    
    // Eliminar guiones al inicio y al final
    $text = trim($text, '-');
    
    // Convertir a minúsculas
    $text = strtolower($text);
    
    // Si está vacío, devolver 'n-a'
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

/**
 * Validar que una fecha tenga formato correcto
 * 
 * @param string $date Fecha a validar
 * @param string $format Formato esperado
 * @return boolean
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Formatear una fecha
 * 
 * @param string $date Fecha a formatear
 * @param string $format Formato de salida
 * @return string Fecha formateada
 */
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) {
        return '';
    }
    
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    
    return $date->format($format);
}

/**
 * Formatear un precio
 * 
 * @param float $price Precio a formatear
 * @param string $currency Símbolo de moneda
 * @param int $decimals Número de decimales
 * @return string Precio formateado
 */
function formatPrice($price, $currency = '$', $decimals = 2) {
    return $currency . number_format($price, $decimals, '.', ',');
}

/**
 * Generar un token aleatorio
 * 
 * @param int $length Longitud del token
 * @return string Token generado
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Verificar si el usuario actual tiene un permiso específico
 * 
 * @param string $permission Código del permiso a verificar
 * @return boolean
 */
function hasPermission($permission) {
    // Si no hay usuario logueado, no tiene permisos
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Si es superadmin, tiene todos los permisos
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        return true;
    }
    
    // Verificar si el usuario tiene el permiso específico
    if (isset($_SESSION['permissions']) && is_array($_SESSION['permissions'])) {
        return in_array($permission, $_SESSION['permissions']);
    }
    
    return false;
}

/**
 * Obtener el mensaje flash de sesión y eliminarlo
 * 
 * @param string $key Clave del mensaje
 * @param mixed $default Valor por defecto si no existe
 * @return mixed
 */
function flash($key, $default = null) {
    if (isset($_SESSION[$key])) {
        $value = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $value;
    }
    
    return $default;
}

/**
 * Establecer un mensaje flash en sesión
 * 
 * @param string $key Clave del mensaje
 * @param mixed $value Valor del mensaje
 * @return void
 */
function setFlash($key, $value) {
    $_SESSION[$key] = $value;
}

/**
 * Limitar una cadena a un número de caracteres
 * 
 * @param string $string Cadena a limitar
 * @param int $limit Límite de caracteres
 * @param string $append Texto a agregar si se limita
 * @return string
 */
function strLimit($string, $limit = 100, $append = '...') {
    if (strlen($string) <= $limit) {
        return $string;
    }
    
    return substr($string, 0, $limit) . $append;
}

/**
 * Generar un array con rango de años para formularios
 * 
 * @param int $start Año de inicio
 * @param int $end Año de fin (0 para año actual)
 * @return array
 */
function yearRange($start, $end = 0) {
    $end = ($end === 0) ? date('Y') : $end;
    $years = range($start, $end);
    return array_combine($years, $years);
}

/**
 * Generar un array con meses para formularios
 * 
 * @return array
 */
function monthsArray() {
    return [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];
}

/**
 * Verificar si es una petición AJAX
 * 
 * @return boolean
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Verificar si el usuario está autenticado
 * 
 * @return boolean
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Obtener el ID del usuario autenticado
 * 
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Genera una URL única para recursos estáticos (para evitar caché)
 * 
 * @param string $path Ruta al recurso
 * @return string URL con parámetro de versión
 */
function asset($path) {
    $filePath = __DIR__ . '/../public/' . ltrim($path, '/');
    $version = file_exists($filePath) ? filemtime($filePath) : time();
    
    return '/public/' . ltrim($path, '/') . '?v=' . $version;
}

/**
 * Verifica si la URL actual coincide con un patrón
 * 
 * @param string $pattern Patrón a verificar
 * @return bool True si coincide, false si no
 */
function isCurrentUrl($pattern) {
    $currentUrl = $_SERVER['REQUEST_URI'];
    
    if ($pattern === '/') {
        return $currentUrl === '/';
    }
    
    return strpos($currentUrl, $pattern) === 0;
}
/**
 * Valida un token CSRF
 * 
 * @param string $token Token CSRF a validar
 * @return bool True si el token es válido, false en caso contrario
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
}