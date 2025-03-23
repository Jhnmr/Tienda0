<?php

/**
 * Middleware de autenticación para API
 * 
 * Verifica que las solicitudes a la API contengan un token válido
 */

// Prevenir acceso directo al archivo
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

class apiauth
{
    /**
     * Verifica si la solicitud API contiene un token válido
     * 
     * @param array $params Parámetros adicionales
     * @return mixed|null Null si el token es válido, respuesta de error en caso contrario
     */
    public function handle($params = [])
    {
        // Obtener el token de autorización
        $token = $this->getAuthToken();

        // Si no hay token
        if (!$token) {
            return $this->unauthorizedResponse('Token de autenticación no proporcionado');
        }

        // Validar el token (JWT o API key según corresponda)
        if (strpos($token, 'Bearer ') === 0) {
            // Es un token JWT
            $jwtToken = substr($token, 7);
            return $this->validateJwtToken($jwtToken);
        } else {
            // Es una API key
            return $this->validateApiKey($token);
        }
    }

    /**
     * Obtiene el token de autorización de los encabezados
     * 
     * @return string|null
     */
    private function getAuthToken()
    {
        // Verificar si hay encabezado de autorización
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        // Algunos servidores lo ponen en REDIRECT_HTTP_AUTHORIZATION
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        // También puede venir como parámetro api_key
        if (isset($_GET['api_key'])) {
            return $_GET['api_key'];
        }

        // No hay token
        return null;
    }

    /**
     * Valida un token JWT
     * 
     * @param string $token Token JWT a validar
     * @return mixed|null Null si el token es válido, respuesta de error en caso contrario
     */
    private function validateJwtToken($token)
    {
        try {
            // Aquí iría la lógica para validar el token JWT
            // Por ejemplo, usando la biblioteca JWT para PHP

            // Ejemplo simplificado (reemplazar con lógica real):
            $isValid = true; // validateJwt($token);

            if (!$isValid) {
                return $this->unauthorizedResponse('Token JWT inválido');
            }

            // Si el token es válido, se podría establecer información del usuario
            // en alguna variable global o en el contexto de la aplicación

            return null;
        } catch (Exception $e) {
            return $this->unauthorizedResponse('Error al validar token: ' . $e->getMessage());
        }
    }

    /**
     * Valida una API key
     * 
     * @param string $apiKey API key a validar
     * @return mixed|null Null si la API key es válida, respuesta de error en caso contrario
     */
    private function validateApiKey($apiKey)
    {
        // Aquí iría la lógica para validar la API key contra la base de datos

        // Ejemplo simplificado (reemplazar con lógica real):
        $isValid = true; // checkApiKeyInDatabase($apiKey);

        if (!$isValid) {
            return $this->unauthorizedResponse('API key inválida');
        }

        return null;
    }

    /**
     * Devuelve una respuesta de error 401 Unauthorized
     * 
     * @param string $message Mensaje de error
     * @return void
     */
    private function unauthorizedResponse($message)
    {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');
        header('WWW-Authenticate: Bearer error="invalid_token"');

        echo json_encode([
            'status' => 'error',
            'code' => 401,
            'message' => $message
        ]);

        exit;
    }
}
