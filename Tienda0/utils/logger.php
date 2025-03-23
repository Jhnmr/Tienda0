<?php
/**
 * Sistema de registro de actividad
 */

/**
 * Registra un mensaje en el log
 * 
 * @param string $level Nivel del mensaje (info, warning, error, debug)
 * @param string $message Mensaje a registrar
 * @param array $context Contexto adicional
 * @return bool True si se registró, false si no
 */
function logMessage($level, $message, array $context = []) {
    // Validar nivel
    $validLevels = ['info', 'warning', 'error', 'debug'];
    if (!in_array($level, $validLevels)) {
        $level = 'info';
    }
    
    // Crear directorio de logs si no existe
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Archivo de log diario
    $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
    
    // Formatear mensaje
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userId = $_SESSION['user_id'] ?? 0;
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    
    $logEntry = "[$timestamp] [$level] [IP: $ip] [User: $userId] $message$contextStr" . PHP_EOL;
    
    // Escribir en el archivo
    return error_log($logEntry, 3, $logFile);
}

/**
 * Registra un mensaje de información
 * 
 * @param string $message Mensaje a registrar
 * @param array $context Contexto adicional
 * @return bool True si se registró, false si no
 */
function logInfo($message, array $context = []) {
    return logMessage('info', $message, $context);
}

/**
 * Registra un mensaje de advertencia
 * 
 * @param string $message Mensaje a registrar
 * @param array $context Contexto adicional
 * @return bool True si se registró, false si no
 */
function logWarning($message, array $context = []) {
    return logMessage('warning', $message, $context);
}

/**
 * Registra un mensaje de error
 * 
 * @param string $message Mensaje a registrar
 * @param array $context Contexto adicional
 * @return bool True si se registró, false si no
 */
function logError($message, array $context = []) {
    return logMessage('error', $message, $context);
}

/**
 * Registra un mensaje de depuración
 * 
 * @param string $message Mensaje a registrar
 * @param array $context Contexto adicional
 * @return bool True si se registró, false si no
 */
function logDebug($message, array $context = []) {
    // Solo registrar mensajes de depuración si está habilitado
    $config = require_once __DIR__ . '/../config/config.php';
    if (!isset($config['app']['debug']) || !$config['app']['debug']) {
        return true;
    }
    
    return logMessage('debug', $message, $context);
}

/**
 * Registra una excepción
 * 
 * @param Exception $exception Excepción a registrar
 * @param array $context Contexto adicional
 * @return bool True si se registró, false si no
 */
function logException(Exception $exception, array $context = []) {
    $message = get_class($exception) . ': ' . $exception->getMessage() . 
               ' in ' . $exception->getFile() . ' on line ' . $exception->getLine();
    
    // Añadir stack trace al contexto
    $context['trace'] = $exception->getTraceAsString();
    
    return logError($message, $context);
}