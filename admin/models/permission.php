<?php
class permission {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtiene todos los permisos
     * 
     * @return array Array de permisos
     */
    public function getAll() {
        try {
            $stmt = $this->db->query("
                SELECT id_permiso, codigo, descripcion, modulo 
                FROM permiso 
                ORDER BY modulo, codigo
            ");
            
            $permissions = [];
            while ($row = $stmt->fetch()) {
                $permissions[] = [
                    'id' => $row['id_permiso'],
                    'codigo' => $row['codigo'],
                    'descripcion' => $row['descripcion'],
                    'modulo' => $row['modulo']
                ];
            }
            
            return [
                'success' => true,
                'permissions' => $permissions
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener permisos: ' . $e->getMessage(),
                'permissions' => []
            ];
        }
    }
    
    /**
     * Obtiene permisos por módulo
     * 
     * @param string $module Nombre del módulo
     * @return array Array de permisos del módulo
     */
    public function getByModule($module) {
        try {
            $stmt = $this->db->prepare("
                SELECT id_permiso, codigo, descripcion, modulo 
                FROM permiso 
                WHERE modulo = :modulo
                ORDER BY codigo
            ");
            
            $stmt->execute([':modulo' => $module]);
            
            $permissions = [];
            while ($row = $stmt->fetch()) {
                $permissions[] = [
                    'id' => $row['id_permiso'],
                    'codigo' => $row['codigo'],
                    'descripcion' => $row['descripcion'],
                    'modulo' => $row['modulo']
                ];
            }
            
            return [
                'success' => true,
                'permissions' => $permissions
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener permisos del módulo: ' . $e->getMessage(),
                'permissions' => []
            ];
        }
    }
    
    /**
     * Obtiene un permiso por su ID
     * 
     * @param int $id ID del permiso
     * @return array Datos del permiso
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id_permiso, codigo, descripcion, modulo 
                FROM permiso 
                WHERE id_permiso = :id
            ");
            
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Permiso no encontrado'
                ];
            }
            
            $permission = $stmt->fetch();
            
            return [
                'success' => true,
                'permission' => [
                    'id' => $permission['id_permiso'],
                    'codigo' => $permission['codigo'],
                    'descripcion' => $permission['descripcion'],
                    'modulo' => $permission['modulo']
                ]
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener el permiso: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crea un nuevo permiso
     * 
     * @param array $permissionData Datos del permiso
     * @return array Resultado de la operación
     */
    public function create($permissionData) {
        try {
            // Verificar si el código ya existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM permiso WHERE codigo = :codigo");
            $checkStmt->execute([':codigo' => $permissionData['codigo']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'Ya existe un permiso con ese código'
                ];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO permiso (codigo, descripcion, modulo) 
                VALUES (:codigo, :descripcion, :modulo)
            ");
            
            $stmt->execute([
                ':codigo' => $permissionData['codigo'],
                ':descripcion' => $permissionData['descripcion'],
                ':modulo' => $permissionData['modulo']
            ]);
            
            $permissionId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'id' => $permissionId,
                'message' => 'Permiso creado correctamente'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al crear el permiso: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualiza un permiso
     * 
     * @param int $id ID del permiso
     * @param array $permissionData Datos del permiso
     * @return array Resultado de la operación
     */
    public function update($id, $permissionData) {
        try {
            // Verificar si el código ya existe (excepto para este permiso)
            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) FROM permiso 
                WHERE codigo = :codigo AND id_permiso != :id_permiso
            ");
            
            $checkStmt->execute([
                ':codigo' => $permissionData['codigo'],
                ':id_permiso' => $id
            ]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'Ya existe un permiso con ese código'
                ];
            }
            
            $stmt = $this->db->prepare("
                UPDATE permiso 
                SET codigo = :codigo, descripcion = :descripcion, modulo = :modulo 
                WHERE id_permiso = :id_permiso
            ");
            
            $stmt->execute([
                ':codigo' => $permissionData['codigo'],
                ':descripcion' => $permissionData['descripcion'],
                ':modulo' => $permissionData['modulo'],
                ':id_permiso' => $id
            ]);
            
            return [
                'success' => true,
                'message' => 'Permiso actualizado correctamente'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar el permiso: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Elimina un permiso
     * 
     * @param int $id ID del permiso
     * @return array Resultado de la operación
     */
    public function delete($id) {
        try {
            // Verificar si el permiso está asignado a algún rol
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM rol_permiso WHERE id_permiso = :id_permiso");
            $checkStmt->execute([':id_permiso' => $id]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar el permiso porque está asignado a uno o más roles'
                ];
            }
            
            $stmt = $this->db->prepare("DELETE FROM permiso WHERE id_permiso = :id_permiso");
            $stmt->execute([':id_permiso' => $id]);
            
            return [
                'success' => true,
                'message' => 'Permiso eliminado correctamente'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar el permiso: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene los módulos disponibles
     * 
     * @return array Array de módulos
     */
    public function getModules() {
        try {
            $stmt = $this->db->query("SELECT DISTINCT modulo FROM permiso ORDER BY modulo");
            
            $modules = [];
            while ($row = $stmt->fetch()) {
                $modules[] = $row['modulo'];
            }
            
            return [
                'success' => true,
                'modules' => $modules
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener módulos: ' . $e->getMessage(),
                'modules' => []
            ];
        }
    }
}