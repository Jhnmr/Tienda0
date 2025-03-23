<?php
class role {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtiene todos los roles
     * 
     * @return array Array de roles
     */
    public function getAll() {
        try {
            $stmt = $this->db->query("SELECT id_rol, nombre, descripcion FROM rol ORDER BY id_rol");
            
            $roles = [];
            while ($row = $stmt->fetch()) {
                $roles[] = [
                    'id' => $row['id_rol'],
                    'nombre' => $row['nombre'],
                    'descripcion' => $row['descripcion']
                ];
            }
            
            return [
                'success' => true,
                'roles' => $roles
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener roles: ' . $e->getMessage(),
                'roles' => []
            ];
        }
    }
    
    /**
     * Obtiene un rol por su ID
     * 
     * @param int $id ID del rol
     * @return array|false Datos del rol o false si no existe
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id_rol, nombre, descripcion FROM rol WHERE id_rol = :id");
            
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Rol no encontrado'
                ];
            }
            
            $role = $stmt->fetch();
            
            // Obtener permisos asociados al rol
            $permissionsStmt = $this->db->prepare("
                SELECT p.id_permiso, p.codigo, p.descripcion, p.modulo
                FROM permiso p
                JOIN rol_permiso rp ON p.id_permiso = rp.id_permiso
                WHERE rp.id_rol = :id_rol
                ORDER BY p.modulo, p.codigo
            ");
            
            $permissionsStmt->execute([':id_rol' => $id]);
            
            $permissions = [];
            while ($permRow = $permissionsStmt->fetch()) {
                $permissions[] = [
                    'id' => $permRow['id_permiso'],
                    'codigo' => $permRow['codigo'],
                    'descripcion' => $permRow['descripcion'],
                    'modulo' => $permRow['modulo']
                ];
            }
            
            return [
                'success' => true,
                'role' => [
                    'id' => $role['id_rol'],
                    'nombre' => $role['nombre'],
                    'descripcion' => $role['descripcion'],
                    'permissions' => $permissions
                ]
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener el rol: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crea un nuevo rol
     * 
     * @param array $roleData Datos del rol
     * @return array Resultado de la operación
     */
    public function create($roleData) {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("INSERT INTO rol (nombre, descripcion) VALUES (:nombre, :descripcion)");
            
            $stmt->execute([
                ':nombre' => $roleData['nombre'],
                ':descripcion' => $roleData['descripcion'] ?? ''
            ]);
            
            $roleId = $this->db->lastInsertId();
            
            // Asignar permisos si se proporcionan
            if (isset($roleData['permissions']) && is_array($roleData['permissions'])) {
                $insertPermStmt = $this->db->prepare("
                    INSERT INTO rol_permiso (id_rol, id_permiso) 
                    VALUES (:id_rol, :id_permiso)
                ");
                
                foreach ($roleData['permissions'] as $permId) {
                    $insertPermStmt->execute([
                        ':id_rol' => $roleId,
                        ':id_permiso' => $permId
                    ]);
                }
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'id' => $roleId,
                'message' => 'Rol creado correctamente'
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            return [
                'success' => false,
                'message' => 'Error al crear el rol: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualiza un rol
     * 
     * @param int $id ID del rol
     * @param array $roleData Datos del rol
     * @return array Resultado de la operación
     */
    public function update($id, $roleData) {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE rol SET nombre = :nombre, descripcion = :descripcion 
                WHERE id_rol = :id_rol
            ");
            
            $stmt->execute([
                ':nombre' => $roleData['nombre'],
                ':descripcion' => $roleData['descripcion'] ?? '',
                ':id_rol' => $id
            ]);
            
            // Actualizar permisos si se proporcionan
            if (isset($roleData['permissions']) && is_array($roleData['permissions'])) {
                // Eliminar permisos actuales
                $deletePermsStmt = $this->db->prepare("DELETE FROM rol_permiso WHERE id_rol = :id_rol");
                $deletePermsStmt->execute([':id_rol' => $id]);
                
                // Insertar nuevos permisos
                $insertPermStmt = $this->db->prepare("
                    INSERT INTO rol_permiso (id_rol, id_permiso) 
                    VALUES (:id_rol, :id_permiso)
                ");
                
                foreach ($roleData['permissions'] as $permId) {
                    $insertPermStmt->execute([
                        ':id_rol' => $id,
                        ':id_permiso' => $permId
                    ]);
                }
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Rol actualizado correctamente'
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            return [
                'success' => false,
                'message' => 'Error al actualizar el rol: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Elimina un rol
     * 
     * @param int $id ID del rol
     * @return array Resultado de la operación
     */
    public function delete($id) {
        try {
            // Verificar si el rol está en uso
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM usuario_rol WHERE id_rol = :id_rol");
            $checkStmt->execute([':id_rol' => $id]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar el rol porque está asignado a uno o más usuarios'
                ];
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Eliminar permisos asociados al rol
            $deletePermsStmt = $this->db->prepare("DELETE FROM rol_permiso WHERE id_rol = :id_rol");
            $deletePermsStmt->execute([':id_rol' => $id]);
            
            // Eliminar el rol
            $deleteRoleStmt = $this->db->prepare("DELETE FROM rol WHERE id_rol = :id_rol");
            $deleteRoleStmt->execute([':id_rol' => $id]);
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Rol eliminado correctamente'
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            return [
                'success' => false,
                'message' => 'Error al eliminar el rol: ' . $e->getMessage()
            ];
        }
    }
}