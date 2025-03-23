<?php
/**
 * Clase principal de la aplicación
 * Inicializa y coordina todos los componentes del sistema
 */

// Prevenir acceso directo al archivo
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

class app {
    /**
     * Instancia de Router
     * @var router
     */
    private $router;
    
    /**
     * Instancia de la conexión de base de datos
     * @var PDO
     */
    private $db;
    
    /**
     * Configuración de la aplicación
     * @var array
     */
    private $config;
    
    /**
     * Modo de depuración
     * @var boolean
     */
    private $debug = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Cargar configuración
        $this->loadConfig();
        
        // Configurar zona horaria
        date_default_timezone_set($this->config['timezone']);
        
        // Inicializar depuración
        $this->debug = $this->config['environment'] === 'development';
        
        // Configurar manejo de errores
        $this->setupErrorHandling();
        
        // Inicializar sesión
        $this->startSession();
        
        // Inicializar Router
        $this->router = new router($this->config);
        
        // Conectar a la base de datos
        $this->connectDatabase();
    }
    
    /**
     * Conecta a la base de datos
     */
    private function connectDatabase() {
        try {
            // Cargar configuración de base de datos
            $dbConfig = require CONFIGPATH . 'database.php';
            
            // Seleccionar configuración basada en entorno
            $environment = $this->config['environment'];
            $config = $dbConfig[$environment];
            
            // Crear DSN
            $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            // Crear conexión PDO
            $this->db = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
            
            return true;
        } catch (PDOException $e) {
            if ($this->debug) {
                die("Error de conexión a la base de datos: " . $e->getMessage());
            } else {
                $this->logError([
                    'type' => $e->getCode(),
                    'message' => "Error de conexión a la base de datos: " . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->showErrorPage();
            }
            
            return false;
        }
    }
    
    /**
     * Obtiene la instancia de conexión a la base de datos
     * @return PDO Instancia de PDO
     */
    public function getDB() {
        return $this->db;
    }
    
    /**
     * Ejecuta la aplicación
     */
    public function run() {
        // Verificar si el sistema está en mantenimiento
        if ($this->config['maintenance']['enabled']) {
            $allowedIps = $this->config['maintenance']['allowed_ips'];
            $clientIp = $_SERVER['REMOTE_ADDR'];
            
            if (!in_array($clientIp, $allowedIps)) {
                header('HTTP/1.1 503 Service Unavailable');
                echo $this->config['maintenance']['message'];
                exit;
            }
        }
        
        // Obtener la URI de la solicitud
        $uri = $this->getRequestUri();
        
        // Obtener el método HTTP
        $method = $this->getRequestMethod();
        
        // Verificar token CSRF para métodos no seguros (POST, PUT, DELETE)
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            $this->verifyCsrfToken();
        }
        
        // Resolver la ruta
        $response = $this->router->resolve($uri, $method);
        
        // Si la respuesta es un array, convertirlo a JSON (para API)
        if (is_array($response)) {
            header('Content-Type: application/json');
            echo json_encode($response);
        } 
        // Si la respuesta es un string, devolverlo directamente
        else if (is_string($response)) {
            echo $response;
        }
    }
    
    /**
     * Obtiene la URI de la solicitud actual
     * @return string
     */
    private function getRequestUri() {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Eliminar la base_url si está presente
        $baseUrl = parse_url($this->config['base_url'], PHP_URL_PATH);
        if ($baseUrl && strpos($uri, $baseUrl) === 0) {
            $uri = substr($uri, strlen($baseUrl));
        }
        
        // Eliminar query string si existe
        $position = strpos($uri, '?');
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }
        
        // Si se usa el parámetro url desde .htaccess
        if (isset($_GET['url'])) {
            $uri = $_GET['url'];
        }
        
        return $uri;
    }
    
    /**
     * Obtiene el método HTTP de la solicitud actual
     * @return string
     */
    private function getRequestMethod() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Soporte para _method en formularios para PUT y DELETE
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
            if (!in_array($method, ['PUT', 'DELETE'])) {
                $method = 'POST';
            }
        }
        
        return $method;
    }
    
    /**
     * Verifica el token CSRF
     */
    private function verifyCsrfToken() {
        // Si la solicitud es AJAX y tiene el encabezado X-CSRF-TOKEN
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } 
        // Si la solicitud es un formulario
        else if (isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } else {
            // No hay token CSRF
            $this->csrfTokenFailed();
            return;
        }
        
        // Verificar token
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            $this->csrfTokenFailed();
        }
        
        // Regenerar token si está configurado
        if ($this->config['security']['csrf_regenerate']) {
            $this->generateCsrfToken();
        }
    }
    
    /**
     * Genera un nuevo token CSRF
     * @return string
     */
    public function generateCsrfToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    /**
     * Maneja un fallo en la verificación del token CSRF
     */
    private function csrfTokenFailed() {
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
     * Comprueba si la solicitud actual es AJAX
     * @return boolean
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Obtiene el Router
     * @return router
     */
    public function getRouter() {
        return $this->router;
    }
    
    /**
     * Muestra un error en desarrollo
     * @param array $error Información del error
     */
    private function displayError($error) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;">';
        echo '<h3>Error</h3>';
        echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($error['message']) . '</p>';
        echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($error['file']) . '</p>';
        echo '<p><strong>Línea:</strong> ' . $error['line'] . '</p>';
        if (isset($error['trace'])) {
            echo '<pre>' . htmlspecialchars($error['trace']) . '</pre>';
        }
        echo '</div>';
        exit;
    }
    
    /**
     * Registra un error en el archivo de log
     * @param array $error Información del error
     */
    private function logError($error) {
        $logMessage = date($this->config['log']['date_format']) . ' - ';
        $logMessage .= 'Error: ' . $error['message'] . ' | ';
        $logMessage .= 'Archivo: ' . $error['file'] . ' | ';
        $logMessage .= 'Línea: ' . $error['line'] . PHP_EOL;
        
        if (isset($error['trace'])) {
            $logMessage .= 'Traza: ' . $error['trace'] . PHP_EOL;
        }
        
        // Asegurar que el directorio de logs existe
        if (!is_dir(LOGSPATH)) {
            mkdir(LOGSPATH, 0755, true);
        }
        
        // Escribir en el archivo de log
        file_put_contents(
            LOGSPATH . 'error.log',
            $logMessage,
            FILE_APPEND
        );
    }
    
    /**
     * Muestra una página de error genérica
     */
    private function showErrorPage() {
        http_response_code(500);
        if (file_exists(VIEWSPATH . 'errors/500.php')) {
            include VIEWSPATH . 'errors/500.php';
        } else {
            echo '<h1>Error interno del servidor</h1>';
            echo '<p>Lo sentimos, ha ocurrido un error inesperado. Por favor, inténtelo de nuevo más tarde.</p>';
        }
        exit;
    }
    
    /**
     * Carga la configuración del sistema
     */
    private function loadConfig() {
        // Cargar configuración principal
        $this->config = require CONFIGPATH . 'config.php';
        
        // Cargar constantes
        require_once CONFIGPATH . 'constants.php';
    }
    
    /**
     * Inicia la sesión con configuraciones seguras
     */
    private function startSession() {
        // Configurar opciones de sesión
        $sessionConfig = $this->config['session'];
        
        // Configurar cookies de sesión
        session_name($sessionConfig['name']);
        
        ini_set('session.cookie_lifetime', $sessionConfig['lifetime']);
        ini_set('session.gc_maxlifetime', $sessionConfig['lifetime']);
        
        // Configurar parámetros de cookie
        session_set_cookie_params([
            'lifetime' => $sessionConfig['lifetime'],
            'path' => $sessionConfig['path'],
            'domain' => $sessionConfig['domain'],
            'secure' => $sessionConfig['secure'],
            'httponly' => $sessionConfig['httponly'],
            'samesite' => $sessionConfig['samesite']
        ]);
        
        // Establecer directorio de almacenamiento de sesión si está definido
        if ($sessionConfig['save_path'] !== null) {
            session_save_path($sessionConfig['save_path']);
        }
        
        // Iniciar sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerar ID de sesión periódicamente para prevenir ataques de fijación de sesión
        if (!isset($_SESSION['last_regeneration'])) {
            $this->regenerateSession();
        } else {
            // Regenerar ID de sesión cada 30 minutos
            $regenerationTime = 30 * 60; // 30 minutos en segundos
            if (time() - $_SESSION['last_regeneration'] > $regenerationTime) {
                $this->regenerateSession();
            }
        }
    }
    
    /**
     * Regenera el ID de sesión
     */
    private function regenerateSession() {
        // Regenerar ID de sesión
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Guardar datos de sesión actuales
            $old_session_data = $_SESSION;
            
            // Regenerar ID de sesión
            session_regenerate_id(true);
            
            // Restaurar datos de sesión
            $_SESSION = $old_session_data;
            
            // Actualizar tiempo de regeneración
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Configura el manejo de errores
     */
    private function setupErrorHandling() {
        // En desarrollo mostrar todos los errores
        if ($this->debug) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);
        }
        
        // Registrar un manejador de errores personalizado
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            if (!(error_reporting() & $errno)) {
                // Este código de error no está incluido en error_reporting
                return false;
            }
            
            $error = [
                'type' => $errno,
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline
            ];
            
            if ($this->debug) {
                // En desarrollo, mostrar errores
                $this->displayError($error);
            } else {
                // En producción, registrar errores en log
                $this->logError($error);
            }
            
            return true;
        });
        
        // Registrar manejador de excepciones
        set_exception_handler(function($exception) {
            $error = [
                'type' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
            
            if ($this->debug) {
                // En desarrollo, mostrar excepciones
                $this->displayError($error);
            } else {
                // En producción, registrar excepciones en log
                $this->logError($error);
                // Mostrar página de error
                $this->showErrorPage();
            }
        });
        
        // Registrar callback para errores fatales
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                if ($this->debug) {
                    // En desarrollo, mostrar errores fatales
                    $this->displayError($error);
                } else {
                    // En producción, registrar errores fatales en log
                    $this->logError($error);
                    // Mostrar página de error
                    $this->showErrorPage();
                }
            }
        });
    }
}