<?php
/**
 * Archivo de funciones auxiliares específicas para roles y permisos
 */

/**
 * Obtiene los módulos disponibles para permisos
 * 
 * @return array Lista de módulos
 */
function getPermissionModules() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT DISTINCT modulo FROM permiso ORDER BY modulo");
    
    $modules = [];
    while ($row = $stmt->fetch()) {
        $modules[] = $row['modulo'];
    }
    
    return $modules;
}

/**
 * Obtiene permisos agrupados por módulo
 * 
 * @return array Permisos agrupados por módulo
 */
function getPermissionsByModule() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT id_permiso, codigo, descripcion, modulo 
        FROM permiso 
        ORDER BY modulo, codigo
    ");
    
    $permissionsByModule = [];
    while ($row = $stmt->fetch()) {
        $permissionsByModule[$row['modulo']][] = [
            'id' => $row['id_permiso'],
            'codigo' => $row['codigo'],
            'descripcion' => $row['descripcion']
        ];
    }
    
    return $permissionsByModule;
}

/**
 * Verifica si un usuario tiene un permiso específico para un recurso específico
 * (Útil para verificar permisos sobre un registro concreto)
 * 
 * @param string $permission Código del permiso
 * @param int $resourceId ID del recurso
 * @param string $resourceType Tipo de recurso
 * @return bool True si tiene permiso, false si no
 */
function userHasResourcePermission($permission, $resourceId, $resourceType) {
    // Si no está autenticado, no tiene permisos
    if (!isAuthenticated()) {
        return false;
    }
    
    // Si tiene el permiso general, lo permitimos
    if (userHasPermission($permission)) {
        // Si es administrador, permitir acceso a todos los recursos
        if (userHasRole(1)) { // 1 = Administrador
            return true;
        }
        
        // En caso contrario, verificar si el recurso pertenece al usuario
        // Esta implementación dependerá de la estructura de tus tablas
        $db = Database::getInstance()->getConnection();
        
        // Verificación para diferentes tipos de recursos
        switch ($resourceType) {
            case 'producto':
                // El usuario solo puede ver sus propios productos
                $stmt = $db->prepare("SELECT COUNT(*) FROM producto WHERE id_producto = :id AND id_usuario = :id_usuario");
                $stmt->execute([
                    ':id' => $resourceId,
                    ':id_usuario' => $_SESSION['user_id']
                ]);
                return $stmt->fetchColumn() > 0;
                
            case 'pedido':
                // El usuario solo puede ver sus propios pedidos
                $stmt = $db->prepare("SELECT COUNT(*) FROM pedido WHERE id_pedido = :id AND id_usuario = :id_usuario");
                $stmt->execute([
                    ':id' => $resourceId,
                    ':id_usuario' => $_SESSION['user_id']
                ]);
                return $stmt->fetchColumn() > 0;
                
            // Agregar más casos según sea necesario
                
            default:
                return false;
        }
    }
    
    return false;
}

/**
 * Obtiene los usuarios asignados a un rol específico
 * 
 * @param int $roleId ID del rol
 * @return array Array de usuarios con ese rol
 */
function getUsersByRole($roleId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT u.id_usuario, u.email, p.nombres, p.apellidos
        FROM usuario u
        JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario
        LEFT JOIN usuario_perfil p ON u.id_usuario = p.id_usuario
        WHERE ur.id_rol = :id_rol
        ORDER BY p.apellidos, p.nombres
    ");
    
    $stmt->execute([':id_rol' => $roleId]);
    
    $users = [];
    while ($row = $stmt->fetch()) {
        $users[] = [
            'id' => $row['id_usuario'],
            'email' => $row['email'],
            'nombres' => $row['nombres'],
            'apellidos' => $row['apellidos'],
            'nombre_completo' => $row['nombres'] . ' ' . $row['apellidos']
        ];
    }
    
    return $users;
}

/**
 * Obtiene un listado de roles para mostrar en un select
 * 
 * @return array Array de roles [id => nombre]
 */
function getRolesForSelect() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id_rol, nombre FROM rol ORDER BY nombre");
    
    $roles = [];
    while ($row = $stmt->fetch()) {
        $roles[$row['id_rol']] = $row['nombre'];
    }
    
    return $roles;
}

/**
 * Obtiene un listado de permisos para mostrar en un select
 * 
 * @return array Array de permisos [id => nombre]
 */
function getPermissionsForSelect() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id_permiso, CONCAT(modulo, ' - ', descripcion) AS nombre FROM permiso ORDER BY modulo, descripcion");
    
    $permissions = [];
    while ($row = $stmt->fetch()) {
        $permissions[$row['id_permiso']] = $row['nombre'];
    }
    
    return $permissions;
}