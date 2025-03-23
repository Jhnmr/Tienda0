<?php

/**
 * Middleware de autenticación
 * 
 * Verifica que el usuario esté autenticado antes de
 * permitir el acceso a rutas protegidas
 */

// Prevenir acceso directo al archivo
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

class auth
{
    /**
     * Verifica si el usuario está autenticado
     * 
     * @param array $params Parámetros adicionales
     * @return mixed|null Null si está autenticado, respuesta de error en caso contrario
     */
    public function handle($params = [])
    {
        // Verificar si existe sesión de usuario
        if (!isset($_SESSION['user_id'])) {
            // Si es una solicitud AJAX
            if ($this->isAjaxRequest()) {
                header('HTTP/1.1 401 Unauthorized');
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'code' => 401,
                    'message' => 'No autenticado'
                ]);
                exit;
            }

            // Guardar URL solicitada para redirección después del login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

            // Redireccionar a login
            redirect('login');
        }

        // Verificar si la cuenta está activa
        if (isset($_SESSION['user_status']) && $_SESSION['user_status'] != USUARIO_ACTIVO) {
            // Cerrar sesión
            session_destroy();

            // Redireccionar con mensaje
            setFlash('error_message', 'Tu cuenta ha sido desactivada. Por favor, contacta al administrador.');
            redirect('login');
        }

        // Si está autenticado, seguir con la siguiente acción
        return null;
    }

    /**
     * Comprueba si la solicitud actual es AJAX
     * 
     * @return boolean
     */
    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }



        /**
     * Middleware de autenticación
     */
    function authMiddleware() {
        if (!isAuthenticated()) {
            setFlash('error', 'Debe iniciar sesión para acceder a esta página.');
            redirect('/login');
            exit;
        }
    }
}
