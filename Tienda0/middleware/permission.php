<?php
/**
 * Middleware de permisos
 */

/**
 * Middleware de permisos
 * Verifica si el usuario tiene alguno de los permisos especificados
 * 
 * @param array|string $permissions Código o array de códigos de permisos permitidos
 * @param string $redirectTo Ruta a redirigir si no tiene permiso
 */
function permissionMiddleware($permissions, $redirectTo = '/') {
    // Primero verificamos autenticación
    authMiddleware();
    
    // Luego verificamos permiso
    if (!userHasPermission($permissions)) {
        setFlashMessage('error', 'No tiene los permisos necesarios para acceder a esta sección.');
        redirect($redirectTo);
        exit;
    }
}

/**
 * Verifica si un usuario tiene un permiso específico
 * 
 * @param string|array $permissions Código o array de códigos de permisos a verificar
 * @return bool True si tiene el permiso, false si no
 */
function userHasPermission($permissions) {
    if (!isAuthenticated()) {
        return false;
    }
    
    // Administradores tienen todos los permisos
    if (userHasRole(1)) {
        return true;
    }
    
    // Si no se pasan permisos para verificar, consideramos que cualquier usuario autenticado tiene acceso
    if (empty($permissions)) {
        return true;
    }
    
    // Convertimos a array si es un solo permiso
    if (!is_array($permissions)) {
        $permissions = [$permissions];
    }
    
    // Verificamos si el usuario tiene alguno de los permisos requeridos
    $userPermissions = $_SESSION['user_permissions'] ?? [];
    
    return count(array_intersect($permissions, $userPermissions)) > 0;
}