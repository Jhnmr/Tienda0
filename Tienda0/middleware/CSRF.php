<?php
/**
 * Middleware de protección CSRF
 * Verifica que todas las peticiones POST, PUT y DELETE
 * contengan un token CSRF válido
 */

// Prevenir acceso directo al archivo
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

class CSRF {
    /**
     * Verifica el token CSRF
     * 
     * @param array $params Parámetros adicionales
     * @return mixed|null Null si la verificación es exitosa, respuesta de error en caso contrario
     */
    public function handle($params = []) {
        global $app;
        
        // Obtener método de la petición
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Solo verificar en métodos POST, PUT, DELETE
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            // Obtener token desde encabezado o formulario
            $token = null;
            
            // Si es una petición AJAX con encabezado X-CSRF-TOKEN
            if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            } 
            // Si es un formulario tradicional
            else if (isset($_POST['csrf_token'])) {
                $token = $_POST['csrf_token'];
            }
            
            // Verificar si el token existe
            if (!$token) {
                return $this->tokenFailed();
            }
            
            // Verificar validez del token
            if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
                return $this->tokenFailed();
            }
            
            // Verificar tiempo de expiración del token si está configurado
            $config = require_once CONFIGPATH . 'config.php';
            if (isset($_SESSION['csrf_token_time'])) {
                $elapsedTime = time() - $_SESSION['csrf_token_time'];
                if ($elapsedTime > $config['security']['csrf_expire']) {
                    return $this->tokenExpired();
                }
            }
            
            // Regenerar token si está configurado
            if ($config['security']['csrf_regenerate']) {
                $app->generateCsrfToken();
            }
        }
        
        return null;
    }
    
    /**
     * Maneja un fallo en la verificación del token CSRF
     * 
     * @return mixed Respuesta de error
     */
    private function tokenFailed() {
        if ($this->isAjaxRequest()) {
            // Si es una solicitud AJAX, devolver error en JSON
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Token CSRF inválido']);
        } else {
            // Si es una solicitud normal, redirigir a página de error
            http_response_code(403);
            
            if (file_exists(VIEWSPATH . 'errors/403.php')) {
                include VIEWSPATH . 'errors/403.php';
            } else {
                echo '<h1>Acceso Prohibido</h1>';
                echo '<p>La acción ha sido bloqueada por razones de seguridad. Por favor, vuelva a intentarlo.</p>';
            }
        }
        exit;
    }
    
    /**
     * Maneja un token CSRF expirado
     * 
     * @return mixed Respuesta de error
     */
    private function tokenExpired() {
        if ($this->isAjaxRequest()) {
            // Si es una solicitud AJAX, devolver error en JSON
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Token CSRF expirado', 'expired' => true]);
        } else {
            // Si es una solicitud normal, redirigir a página de error
            http_response_code(403);
            
            if (file_exists(VIEWSPATH . 'errors/403.php')) {
                include VIEWSPATH . 'errors/403.php';
            } else {
                echo '<h1>Acceso Prohibido</h1>';
                echo '<p>La sesión ha expirado. Por favor, vuelva a cargar la página e inténtelo de nuevo.</p>';
            }
        }
        exit;
    }
    
    /**
     * Comprueba si la solicitud actual es AJAX
     * 
     * @return boolean
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
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
    
    return $token === $_SESSION['csrf_token'];
}
}