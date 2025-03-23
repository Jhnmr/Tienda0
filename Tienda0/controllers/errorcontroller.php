<?php
/**
 * Controlador de errores
 * 
 * Maneja las respuestas para diferentes errores HTTP
 */

// Prevenir acceso directo al archivo
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

class errorcontroller {
    /**
     * Muestra página de error 404 (No encontrado)
     */
    public function notFound() {
        // Establecer código de estado HTTP
        http_response_code(404);
        
        // Cargar vista
        view('errors.404');
    }
    
    /**
     * Muestra página de error 403 (Prohibido)
     */
    public function forbidden() {
        // Establecer código de estado HTTP
        http_response_code(403);
        
        // Cargar vista
        view('errors.403');
    }
    
    /**
     * Muestra página de error 500 (Error interno del servidor)
     */
    public function serverError() {
        // Establecer código de estado HTTP
        http_response_code(500);
        
        // Cargar vista
        view('errors.500');
    }
    
    /**
     * Muestra página de error 401 (No autorizado)
     */
    public function unauthorized() {
        // Establecer código de estado HTTP
        http_response_code(401);
        
        // Cargar vista
        view('errors.401');
    }
    
    /**
     * Muestra página de error 400 (Solicitud incorrecta)
     */
    public function badRequest() {
        // Establecer código de estado HTTP
        http_response_code(400);
        
        // Cargar vista
        view('errors.400');
    }
    
    /**
     * Muestra página de error 503 (Servicio no disponible)
     */
    public function serviceUnavailable() {
        // Establecer código de estado HTTP
        http_response_code(503);
        
        // Cargar vista
        view('errors.503');
    }
    
    /**
     * Muestra error JSON para API
     * 
     * @param int $statusCode Código de estado HTTP
     * @param string $message Mensaje de error
     * @param array $errors Errores adicionales
     */
    public function apiError($statusCode, $message, $errors = []) {
        // Establecer código de estado HTTP
        http_response_code($statusCode);
        
        // Establecer encabezado Content-Type
        header('Content-Type: application/json');
        
        // Preparar respuesta
        $response = [
            'status' => 'error',
            'code' => $statusCode,
            'message' => $message
        ];
        
        // Agregar errores si existen
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        // Enviar respuesta JSON
        echo json_encode($response);
        exit;
    }
}