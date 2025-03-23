/**
 * Muestra un mensaje flash
 * 
 * @return void
 */
function displayFlashMessage() {
    $message = getFlashMessage();
    
    if ($message) {
        $type = $message['type'];
        $text = e($message['message']);
        
        // Mapear tipo a clase de Bootstrap
        $alertClass = 'alert-info';
        $icon = 'info-circle';
        
        switch ($type) {
            case 'success':
                $alertClass = 'alert-success';
                $icon = 'check-circle';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                $icon = 'exclamation-circle';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                $icon = 'exclamation-triangle';
                break;
        }
        
        echo "<div class='alert $alertClass alert-dismissible fade show' role='alert'>";
        echo "<i class='bi bi-$icon me-2'></i>$text";
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
        echo "</div>";
    }
}

/**
 * Formatea una fecha
 * 
 * @param string $date Fecha en formato Y-m-d
 * @param string $format Formato deseado
 * @return string Fecha formateada
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    
    $datetime = new DateTime($date);
    return $datetime->format($format);
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
 * Trunca un texto a una longitud específica
 * 
 * @param string $text Texto a truncar
 * @param int $length Longitud máxima
 * @param string $append Texto a añadir si se trunca
 * @return string Texto truncado
 */
function truncate($text, $length = 100, $append = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $append;
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

/**
 * Genera un número aleatorio seguro
 * 
 * @param int $min Valor mínimo
 * @param int $max Valor máximo
 * @return int Número aleatorio
 */
function secureRandom($min, $max) {
    return random_int($min, $max);
}

/**
 * Valida una URL
 * 
 * @param string $url URL a validar
 * @return bool True si es válida, false si no
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Obtiene la URL actual
 * 
 * @return string URL actual
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    
    return $protocol . $host . $uri;
}

/**
 * Convierte un array a JSON de forma segura
 * 
 * @param array $data Datos a convertir
 * @return string JSON
 */
function safeJsonEncode($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
}