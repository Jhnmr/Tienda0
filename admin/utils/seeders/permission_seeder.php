<?php
/**
 * Seeder para permisos básicos del sistema
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../../includes/db.php';

class PermissionSeeder {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Ejecutar el seeder
     */
    public function run() {
        // Verificar si ya existen permisos
        $stmt = $this->db->query("SELECT COUNT(*) FROM permiso");
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo "Ya existen permisos en la base de datos. No se ejecutará el seeder.\n";
            return;
        }
        
        // Definir permisos básicos por módulo
        $permissions = [
            // Usuarios
            ['codigo' => 'user_view', 'descripcion' => 'Ver usuarios', 'modulo' => 'Usuarios'],
            ['codigo' => 'user_create', 'descripcion' => 'Crear usuarios', 'modulo' => 'Usuarios'],
            ['codigo' => 'user_edit', 'descripcion' => 'Editar usuarios', 'modulo' => 'Usuarios'],
            ['codigo' => 'user_delete', 'descripcion' => 'Eliminar usuarios', 'modulo' => 'Usuarios'],
            
            // Roles
            ['codigo' => 'role_view', 'descripcion' => 'Ver roles', 'modulo' => 'Roles'],
            ['codigo' => 'role_create', 'descripcion' => 'Crear roles', 'modulo' => 'Roles'],
            ['codigo' => 'role_edit', 'descripcion' => 'Editar roles', 'modulo' => 'Roles'],
            ['codigo' => 'role_delete', 'descripcion' => 'Eliminar roles', 'modulo' => 'Roles'],
            
            // Productos
            ['codigo' => 'product_view', 'descripcion' => 'Ver productos', 'modulo' => 'Productos'],
            ['codigo' => 'product_create', 'descripcion' => 'Crear productos', 'modulo' => 'Productos'],
            ['codigo' => 'product_edit', 'descripcion' => 'Editar productos', 'modulo' => 'Productos'],
            ['codigo' => 'product_delete', 'descripcion' => 'Eliminar productos', 'modulo' => 'Productos'],
            
            // Categorías
            ['codigo' => 'category_view', 'descripcion' => 'Ver categorías', 'modulo' => 'Categorías'],
            ['codigo' => 'category_create', 'descripcion' => 'Crear categorías', 'modulo' => 'Categorías'],
            ['codigo' => 'category_edit', 'descripcion' => 'Editar categorías', 'modulo' => 'Categorías'],
            ['codigo' => 'category_delete', 'descripcion' => 'Eliminar categorías', 'modulo' => 'Categorías'],
            
            // Pedidos
            ['codigo' => 'order_view', 'descripcion' => 'Ver pedidos', 'modulo' => 'Pedidos'],
            ['codigo' => 'order_create', 'descripcion' => 'Crear pedidos', 'modulo' => 'Pedidos'],
            ['codigo' => 'order_edit', 'descripcion' => 'Editar pedidos', 'modulo' => 'Pedidos'],
            ['codigo' => 'order_delete', 'descripcion' => 'Eliminar pedidos', 'modulo' => 'Pedidos'],
            
            // Reportes
            ['codigo' => 'report_sales', 'descripcion' => 'Ver reportes de ventas', 'modulo' => 'Reportes'],
            ['codigo' => 'report_customers', 'descripcion' => 'Ver reportes de clientes', 'modulo' => 'Reportes'],
            ['codigo' => 'report_inventory', 'descripcion' => 'Ver reportes de inventario', 'modulo' => 'Reportes'],
            
            // Configuración
            ['codigo' => 'config_view', 'descripcion' => 'Ver configuración', 'modulo' => 'Configuración'],
            ['codigo' => 'config_edit', 'descripcion' => 'Editar configuración', 'modulo' => 'Configuración']
        ];
        
        // Insertar permisos
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                INSERT INTO permiso (codigo, descripcion, modulo) 
                VALUES (:codigo, :descripcion, :modulo)
            ");
            
            foreach ($permissions as $permission) {
                $stmt->execute([
                    ':codigo' => $permission['codigo'],
                    ':descripcion' => $permission['descripcion'],
                    ':modulo' => $permission['modulo']
                ]);
            }
            
            // Crear roles básicos
            $this->createBasicRoles();
            
            $this->db->commit();
            
            echo "Se han creado " . count($permissions) . " permisos y los roles básicos del sistema.\n";
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            echo "Error al crear permisos: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Crear roles básicos
     */
    private function createBasicRoles() {
        // Crear rol de administrador
        $stmtRole = $this->db->prepare("
            INSERT INTO rol (nombre, descripcion) 
            VALUES (:nombre, :descripcion)
        ");
        
        // Rol Administrador
        $stmtRole->execute([
            ':nombre' => 'Administrador',
            ':descripcion' => 'Acceso completo al sistema'
        ]);
        
        $adminRoleId = $this->db->lastInsertId();
        
        // Rol Cliente
        $stmtRole->execute([
            ':nombre' => 'Cliente',
            ':descripcion' => 'Usuario registrado con acceso al área de clientes'
        ]);
        
        $clientRoleId = $this->db->lastInsertId();
        
        // Asignar todos los permisos al rol administrador
        $stmtPermissions = $this->db->query("SELECT id_permiso FROM permiso");
        $permissions = $stmtPermissions->fetchAll(PDO::FETCH_COLUMN);
        
        $stmtRolePermission = $this->db->prepare("
            INSERT INTO rol_permiso (id_rol, id_permiso) 
            VALUES (:id_rol, :id_permiso)
        ");
        
        foreach ($permissions as $permissionId) {
            $stmtRolePermission->execute([
                ':id_rol' => $adminRoleId,
                ':id_permiso' => $permissionId
            ]);
        }
        
        // Asignar permisos básicos al rol cliente (solo permisos de vista para su perfil)
        $clientPermissions = ['user_view']; // Solo puede ver su propio perfil
        
        $stmtClientPermissions = $this->db->prepare("
            SELECT id_permiso FROM permiso WHERE codigo = :codigo
        ");
        
        foreach ($clientPermissions as $permissionCode) {
            $stmtClientPermissions->execute([':codigo' => $permissionCode]);
            $permissionId = $stmtClientPermissions->fetchColumn();
            
            if ($permissionId) {
                $stmtRolePermission->execute([
                    ':id_rol' => $clientRoleId,
                    ':id_permiso' => $permissionId
                ]);
            }
        }
    }
}