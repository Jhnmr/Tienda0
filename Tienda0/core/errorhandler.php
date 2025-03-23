<?php
/**
 * Manejador de errores de la aplicación
 * 
 * Se encarga de capturar y manejar todos los errores
 * y excepciones de la aplicación
 */

// Prevenir acceso directo al archivo
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

class errorhandler {
    /**
     * Modo de depuración
     * @var boolean
     */
    private $debug;
    
    /**
     * Configuración de log
     * @var array
     */
    private $logConfig;
    
    /**
     * Constructor
     * 
     * @param boolean $debug Modo de depuración
     * @param array $logConfig Configuración de log
     */
    public function __construct($debug = false, $logConfig = []) {
        $this->debug = $debug;
        $this->logConfig = $logConfig;
        
        // Establecer manejadores de errores y excepciones
        $this->registerHandlers();
    }
    
    /**
     * Registra los manejadores de errores y excepciones
     */
    private function registerHandlers() {
        // Manejador de errores
        set_error_handler([$this, 'handleError']);
        
        // Manejador de excepciones
        set_exception_handler([$this, 'handleException']);
        
        // Manejador de errores fatales
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Maneja los errores de PHP
     * 
     * @param int $level Nivel de error
     * @param string $message Mensaje de error
     * @param string $file Archivo donde ocurrió el error
     * @param int $line Línea donde ocurrió el error
     * @return boolean
     */
    public function handleError($level, $message, $file, $line) {
        // Verificar si el error debe ser reportado
        if (!(error_reporting() & $level)) {
            return false;
        }
        
        // Crear array con información del error
        $error = [
            'type' => $level,
            'message' => $message,
            'file' => $file,
            'line' => $line
        ];
        
        // Determinar tipo de error en texto
        switch ($level) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $error['type_str'] = 'Error Fatal';
                break;
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $error['type_str'] = 'Advertencia';
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $error['type_str'] = 'Aviso';
                break;
            case E_STRICT:
                $error['type_str'] = 'Estricto';
                break;
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $error['type_str'] = 'Obsoleto';
                break;
            default:
                $error['type_str'] = 'Desconocido';
        }
        
        // En modo depuración, mostrar el error
        if ($this->debug) {
            $this->displayError($error);
        } else {
            // En producción, registrar en log
            $this->logError($error);
            
            // Si es un error fatal, mostrar página de error
            if (in_array($level, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
                $this->showErrorPage(500);
            }
        }
        
        // Si es un error fatal, terminar ejecución
        if (in_array($level, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            exit;
        }
        
        // Devolver true para evitar que PHP maneje el error
        return true;
    }
    
    /**
     * Maneja las excepciones no capturadas
     * 
     * @param \Throwable $exception Excepción a manejar
     */
    public function handleException($exception) {
        // Crear array con información de la excepción
        $error = [
            'type' => $exception->getCode(),
            'type_str' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        // En modo depuración, mostrar la excepción
        if ($this->debug) {
            $this->displayError($error);
        } else {
            // En producción, registrar en log
            $this->logError($error);
            
            // Mostrar página de error
            $this->showErrorPage(500);
        }
        
        exit;
    }
    
    /**
     * Maneja los errores fatales que ocurren durante el cierre
     */
    public function handleShutdown() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Agregar tipo de error en texto
            $error['type_str'] = 'Error Fatal';
            
            // En modo depuración, mostrar el error
            if ($this->debug) {
                $this->displayError($error);
            } else {
                // En producción, registrar en log
                $this->logError($error);
                
                // Mostrar página de error
                $this->showErrorPage(500);
            }
        }
    }
    
    /**
     * Muestra un error en desarrollo
     * 
     * @param array $error Información del error
     */
    private function displayError($error) {
        // Asegurarse de que no se haya enviado ningún contenido
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;">';
        echo '<h2 style="margin-top: 0;">' . $error['type_str'] . '</h2>';
        echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($error['message']) . '</p>';
        echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($error['file']) . '</p>';
        echo '<p><strong>Línea:</strong> ' . $error['line'] . '</p>';
        
        if (isset($error['trace'])) {
            echo '<h3>Traza:</h3>';
            echo '<pre style="background-color: #f5f5f5; padding: 10px; border-radius: 3px; overflow: auto;">' . htmlspecialchars($error['trace']) . '</pre>';
        }
        
        echo '</div>';
    }
    
    /**
     * Registra un error en el archivo de log
     * 
     * @param array $error Información del error
     */
    private function logError($error) {
        $dateFormat = $this->logConfig['date_format'] ?? 'Y-m-d H:i:s';
        
        $logMessage = date($dateFormat) . ' - ';
        $logMessage .= $error['type_str'] . ': ' . $error['message'] . ' | ';
        $logMessage .= 'Archivo: ' . $error['file'] . ' | ';
        $logMessage .= 'Línea: ' . $error['line'] . PHP_EOL;
        
        if (isset($error['trace'])) {
            $logMessage .= 'Traza: ' . PHP_EOL . $error['trace'] . PHP_EOL;
        }
        
        $logMessage .= '--------------------------------------' . PHP_EOL;
        
        // Asegurar que el directorio de logs existe
        $logPath = $this->logConfig['path'] ?? 'logs/';
        $fullPath = BASEPATH . DIRECTORY_SEPARATOR . $logPath;
        
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        // Escribir en el archivo de log
        file_put_contents(
            $fullPath . 'error.log',
            $logMessage,
            FILE_APPEND
        );
    }
    
    /**
     * Muestra una página de error genérica
     * 
     * @param int $statusCode Código de estado HTTP
     */
    private function showErrorPage($statusCode = 500) {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        $errorFile = VIEWSPATH . 'errors/' . $statusCode . '.php';
        
        if (file_exists($errorFile)) {
            include $errorFile;
        } else {
            // Página de error por defecto
            echo '<!DOCTYPE html>';
            echo '<html lang="es">';
            echo '<head>';
            echo '<meta charset="UTF-8">';
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
            echo '<title>Error ' . $statusCode . '</title>';
            echo '<style>';
            echo 'body { font-family: Arial, sans-serif; background-color: #f7f7f7; color: #333; padding: 40px 20px; text-align: center; }';
            echo '.error-container { max-width: 500px; margin: 0 auto; background-color: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }';
            echo 'h1 { margin-top: 0; color: #e74c3c; }';
            echo '.button { display: inline-block; background-color: #3498db; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 5px; margin-top: 20px; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';
            echo '<div class="error-container">';
            echo '<h1>Error ' . $statusCode . '</h1>';
            
            switch ($statusCode) {
                case 404:
                    echo '<p>Lo sentimos, la página que estás buscando no ha sido encontrada.</p>';
                    break;
                case 403:
                    echo '<p>No tienes permiso para acceder a este recurso.</p>';
                    break;
                default:
                    echo '<p>Lo sentimos, ha ocurrido un error inesperado. Por favor, inténtelo de nuevo más tarde.</p>';
            }
            
            echo '<a href="/" class="button">Volver al inicio</a>';
            echo '</div>';
            echo '</body>';
            echo '</html>';
        }
    }


    /**
 * Sistema de manejo de errores y excepciones
 */

/**
 * Handler para errores de PHP
 * 
 * @param int $errno Nivel del error
 * @param string $errstr Mensaje de error
 * @param string $errfile Archivo donde ocurrió el error
 * @param int $errline Línea donde ocurrió el error
 * @return bool True para prevenir la ejecución del manejador interno de PHP
 */
function appErrorHandler($errno, $errstr, $errfile, $errline) {
    // Obtener la configuración de depuración
    $config = require_once __DIR__ . '/../config/config.php';
    $debug = $config['app']['debug'] ?? false;
    
    // No reportar errores suprimidos con @
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Mapear nivel de error a texto
    switch ($errno) {
        case E_ERROR:
        case E_USER_ERROR:
            $error_level = 'ERROR';
            break;
        case E_WARNING:
        case E_USER_WARNING:
            $error_level = 'WARNING';
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            $error_level = 'NOTICE';
            break;
        default:
            $error_level = 'UNKNOWN';
    }
    
    // Crear mensaje de error para el log
    $error_message = "[$error_level] $errstr en $errfile línea $errline";
    
    // Registrar en log
    error_log($error_message);
    
    // Si es un error fatal o en modo debug, mostrar página de error
    if ($errno == E_ERROR || $errno == E_USER_ERROR || $debug) {
        ob_clean(); // Limpiar el buffer de salida
        
        if ($debug) {
            // Mostrar detalle de error en modo desarrollo
            echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border: 1px solid #f5c6cb; border-radius: 5px;">';
            echo '<h3>Error de Aplicación</h3>';
            echo '<p><strong>Tipo:</strong> ' . $error_level . '</p>';
            echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($errstr) . '</p>';
            echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($errfile) . '</p>';
            echo '<p><strong>Línea:</strong> ' . $errline . '</p>';
            echo '<p><strong>Tiempo:</strong> ' . date('Y-m-d H:i:s') . '</p>';
            echo '</div>';
        } else {
            // En producción, mostrar mensaje genérico
            if (file_exists(__DIR__ . '/../views/errors/500.php')) {
                include __DIR__ . '/../views/errors/500.php';
            } else {
                echo '<h1>Error Interno del Servidor</h1>';
                echo '<p>Ha ocurrido un error inesperado. Por favor, intente más tarde.</p>';
            }
        }
        
        exit(1);
    }
    
    // Permitir que PHP maneje el error
    return false;
}

/**
 * Handler para excepciones no capturadas
 * 
 * @param Throwable $exception La excepción
 * @return void
 */
function appExceptionHandler($exception) {
    // Obtener la configuración de depuración
    $config = require_once __DIR__ . '/../config/config.php';
    $debug = $config['app']['debug'] ?? false;
    
    // Mensaje para el log
    $error_message = "UNCAUGHT EXCEPTION: " . $exception->getMessage() . 
                     " en " . $exception->getFile() . 
                     " línea " . $exception->getLine();
    
    // Registrar en log
    error_log($error_message);
    error_log($exception->getTraceAsString());
    
    // Limpiar cualquier salida previa
    ob_clean();
    
    if ($debug) {
        // Mostrar detalle en modo desarrollo
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border: 1px solid #f5c6cb; border-radius: 5px;">';
        echo '<h3>Excepción No Capturada</h3>';
        echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($exception->getFile()) . '</p>';
        echo '<p><strong>Línea:</strong> ' . $exception->getLine() . '</p>';
        echo '<p><strong>Traza:</strong></p>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
        echo '</div>';
    } else {
        // En producción, mostrar mensaje genérico
        if (file_exists(__DIR__ . '/../views/errors/500.php')) {
            include __DIR__ . '/../views/errors/500.php';
        } else {
            echo '<h1>Error Interno del Servidor</h1>';
            echo '<p>Ha ocurrido un error inesperado. Por favor, intente más tarde.</p>';
        }
    }
    
    exit(1);
}

}
