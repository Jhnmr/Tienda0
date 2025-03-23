<?php
class user {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Crea un nuevo usuario
     * 
     * @param array $userData Datos del usuario
     * @return array Array con 'success' (bool) y 'message' o 'id' (int)
     */
    public function create($userData) {
        try {
            // Verificar si el email ya existe
            if ($this->emailExists($userData['email'])) {
                return [
                    'success' => false,
                    'message' => 'El correo electrónico ya está registrado'
                ];
            }
            
            // Hashear la contraseña
            $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT, [
                'cost' => require(__DIR__ . '/../config/config.php')['security']['bcrypt_cost']
            ]);
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Insertar en la tabla usuario
            $stmt = $this->db->prepare("
                INSERT INTO usuario (email, password_hash, fecha_registro, ultimo_acceso, verificado, id_idioma_preferido, id_segmento_cliente) 
                VALUES (:email, :password_hash, NOW(), NOW(), :verificado, :id_idioma_preferido, :id_segmento_cliente)
            ");
            
            $stmt->execute([
                ':email' => $userData['email'],
                ':password_hash' => $passwordHash,
                ':verificado' => $userData['verificado'] ?? 0,
                ':id_idioma_preferido' => $userData['id_idioma_preferido'] ?? 1,
                ':id_segmento_cliente' => $userData['id_segmento_cliente'] ?? 1
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Insertar en la tabla usuario_perfil
            $stmt = $this->db->prepare("
                INSERT INTO usuario_perfil (id_usuario, nombres, apellidos, telefono, fecha_nacimiento, genero, marketing_consent, fecha_actualizacion) 
                VALUES (:id_usuario, :nombres, :apellidos, :telefono, :fecha_nacimiento, :genero, :marketing_consent, NOW())
            ");
            
            $stmt->execute([
                ':id_usuario' => $userId,
                ':nombres' => $userData['nombres'] ?? '',
                ':apellidos' => $userData['apellidos'] ?? '',
                ':telefono' => $userData['telefono'] ?? '',
                ':fecha_nacimiento' => $userData['fecha_nacimiento'] ?? NULL,
                ':genero' => $userData['genero'] ?? '',
                ':marketing_consent' => $userData['marketing_consent'] ?? 0
            ]);
            
            // Asignar rol al usuario (por defecto rol de cliente)
            $rolId = $userData['rol_id'] ?? 2; // 2 = Cliente (ajustar según tu estructura)
            
            $stmt = $this->db->prepare("
                INSERT INTO usuario_rol (id_usuario, id_rol) 
                VALUES (:id_usuario, :id_rol)
            ");
            
            $stmt->execute([
                ':id_usuario' => $userId,
                ':id_rol' => $rolId
            ]);
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'id' => $userId
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            return [
                'success' => false,
                'message' => 'Error al crear el usuario: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verifica si un email ya existe en la base de datos
     * 
     * @param string $email Email a verificar
     * @return bool True si existe, false si no
     */
    public function emailExists($email) {
        $stmt = $this->db->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Autenticar usuario por email y contraseña
     * 
     * @param string $email Email del usuario
     * @param string $password Contraseña sin hashear
     * @return array Array con 'success' (bool), 'user' (array) si éxito y 'message' (string) si error
     */
    public function authenticate($email, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id_usuario, u.email, u.password_hash, u.verificado, 
                       p.nombres, p.apellidos 
                FROM usuario u
                LEFT JOIN usuario_perfil p ON u.id_usuario = p.id_usuario
                WHERE u.email = :email
            ");
            
            $stmt->execute([':email' => $email]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ];
            }
            
            $user = $stmt->fetch();
            
            // Verificar contraseña
            if (!password_verify($password, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ];
            }
            
            // Verificar si la cuenta está verificada
            if (!$user['verificado']) {
                return [
                    'success' => false,
                    'message' => 'La cuenta no ha sido verificada'
                ];
            }
            
            // Actualizar último acceso
            $updateStmt = $this->db->prepare("
                UPDATE usuario SET ultimo_acceso = NOW() 
                WHERE id_usuario = :id_usuario
            ");
            
            $updateStmt->execute([':id_usuario' => $user['id_usuario']]);
            
            // Obtener roles y permisos del usuario
            $roles = $this->getUserRoles($user['id_usuario']);
            $permissions = $this->getUserPermissions($user['id_usuario']);
            
            unset($user['password_hash']); // Eliminar dato sensible
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id_usuario'],
                    'email' => $user['email'],
                    'nombres' => $user['nombres'],
                    'apellidos' => $user['apellidos'],
                    'roles' => $roles,
                    'permissions' => $permissions
                ]
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al autenticar: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene roles de un usuario
     * 
     * @param int $userId ID del usuario
     * @return array Array de IDs de roles
     */
    public function getUserRoles($userId) {
        $stmt = $this->db->prepare("
            SELECT r.id_rol, r.nombre 
            FROM rol r
            JOIN usuario_rol ur ON r.id_rol = ur.id_rol
            WHERE ur.id_usuario = :id_usuario
        ");
        
        $stmt->execute([':id_usuario' => $userId]);
        
        $roles = [];
        while ($row = $stmt->fetch()) {
            $roles[] = [
                'id' => $row['id_rol'],
                'nombre' => $row['nombre']
            ];
        }
        
        return $roles;
    }
    
    /**
     * Obtiene permisos de un usuario basado en sus roles
     * 
     * @param int $userId ID del usuario
     * @return array Array de códigos de permisos
     */
    public function getUserPermissions($userId) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.id_permiso, p.codigo, p.descripcion, p.modulo
            FROM permiso p
            JOIN rol_permiso rp ON p.id_permiso = rp.id_permiso
            JOIN usuario_rol ur ON rp.id_rol = ur.id_rol
            WHERE ur.id_usuario = :id_usuario
        ");
        
        $stmt->execute([':id_usuario' => $userId]);
        
        $permissions = [];
        while ($row = $stmt->fetch()) {
            $permissions[] = [
                'id' => $row['id_permiso'],
                'codigo' => $row['codigo'],
                'descripcion' => $row['descripcion'],
                'modulo' => $row['modulo']
            ];
        }
        
        return $permissions;
    }
    
    /**
     * Obtiene todos los usuarios
     * 
     * @param int $limit Límite de resultados
     * @param int $offset Offset para paginación
     * @return array Array de usuarios
     */
    public function getAll($limit = 10, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id_usuario, u.email, u.fecha_registro, u.ultimo_acceso, u.verificado,
                       p.nombres, p.apellidos, p.telefono
                FROM usuario u
                LEFT JOIN usuario_perfil p ON u.id_usuario = p.id_usuario
                ORDER BY u.id_usuario DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $users = [];
            while ($row = $stmt->fetch()) {
                $users[] = [
                    'id' => $row['id_usuario'],
                    'email' => $row['email'],
                    'nombres' => $row['nombres'],
                    'apellidos' => $row['apellidos'],
                    'telefono' => $row['telefono'],
                    'fecha_registro' => $row['fecha_registro'],
                    'ultimo_acceso' => $row['ultimo_acceso'],
                    'verificado' => $row['verificado']
                ];
            }
            
            // Obtener total de usuarios para paginación
            $countStmt = $this->db->query("SELECT COUNT(*) FROM usuario");
            $total = $countStmt->fetchColumn();
            
            return [
                'users' => $users,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ];
            
        } catch (PDOException $e) {
            return [
                'users' => [],
                'total' => 0,
                'pages' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene un usuario por su ID
     * 
     * @param int $id ID del usuario
     * @return array|false Datos del usuario o false si no existe
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id_usuario, u.email, u.fecha_registro, u.ultimo_acceso, u.verificado,
                       u.id_idioma_preferido, u.id_segmento_cliente,
                       p.nombres, p.apellidos, p.telefono, p.fecha_nacimiento, 
                       p.genero, p.marketing_consent
                FROM usuario u
                LEFT JOIN usuario_perfil p ON u.id_usuario = p.id_usuario
                WHERE u.id_usuario = :id
            ");
            
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            $user = $stmt->fetch();
            $user['roles'] = $this->getUserRoles($id);
            
            return $user;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Actualiza un usuario
     * 
     * @param int $id ID del usuario
     * @param array $userData Datos a actualizar
     * @return bool True si se actualizó, false si no
     */
    public function update($id, $userData) {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Actualizar tabla usuario
            $userFields = [];
            $userParams = [':id_usuario' => $id];
            
            if (isset($userData['email'])) {
                // Verificar que el nuevo email no exista (si es diferente al actual)
                $currentUser = $this->getById($id);
                if ($currentUser['email'] !== $userData['email'] && $this->emailExists($userData['email'])) {
                    return [
                        'success' => false,
                        'message' => 'El correo electrónico ya está en uso'
                    ];
                }
                
                $userFields[] = "email = :email";
                $userParams[':email'] = $userData['email'];
            }
            
            if (isset($userData['password']) && !empty($userData['password'])) {
                $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT, [
                    'cost' => require(__DIR__ . '/../config/config.php')['security']['bcrypt_cost']
                ]);
                
                $userFields[] = "password_hash = :password_hash";
                $userParams[':password_hash'] = $passwordHash;
            }
            
            if (isset($userData['verificado'])) {
                $userFields[] = "verificado = :verificado";
                $userParams[':verificado'] = $userData['verificado'];
            }
            
            if (isset($userData['id_idioma_preferido'])) {
                $userFields[] = "id_idioma_preferido = :id_idioma_preferido";
                $userParams[':id_idioma_preferido'] = $userData['id_idioma_preferido'];
            }
            
            if (isset($userData['id_segmento_cliente'])) {
                $userFields[] = "id_segmento_cliente = :id_segmento_cliente";
                $userParams[':id_segmento_cliente'] = $userData['id_segmento_cliente'];
            }
            
            // Si hay campos para actualizar en usuario
            if (!empty($userFields)) {
                $sql = "UPDATE usuario SET " . implode(", ", $userFields) . " WHERE id_usuario = :id_usuario";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($userParams);
            }
            
            // Actualizar tabla usuario_perfil
            $profileFields = [];
            $profileParams = [':id_usuario' => $id];
            
            if (isset($userData['nombres'])) {
                $profileFields[] = "nombres = :nombres";
                $profileParams[':nombres'] = $userData['nombres'];
            }
            
            if (isset($userData['apellidos'])) {
                $profileFields[] = "apellidos = :apellidos";
                $profileParams[':apellidos'] = $userData['apellidos'];
            }
            if (isset($userData['telefono'])) {
                $profileFields[] = "telefono = :telefono";
                $profileParams[':telefono'] = $userData['telefono'];
            }
            
            if (isset($userData['fecha_nacimiento'])) {
                $profileFields[] = "fecha_nacimiento = :fecha_nacimiento";
                $profileParams[':fecha_nacimiento'] = $userData['fecha_nacimiento'];
            }
            
            if (isset($userData['genero'])) {
                $profileFields[] = "genero = :genero";
                $profileParams[':genero'] = $userData['genero'];
            }
            
            if (isset($userData['marketing_consent'])) {
                $profileFields[] = "marketing_consent = :marketing_consent";
                $profileParams[':marketing_consent'] = $userData['marketing_consent'];
            }
            
            // Siempre actualizamos la fecha de actualización
            $profileFields[] = "fecha_actualizacion = NOW()";
            
            // Si hay campos para actualizar en perfil
            if (!empty($profileFields)) {
                $sql = "UPDATE usuario_perfil SET " . implode(", ", $profileFields) . " WHERE id_usuario = :id_usuario";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($profileParams);
            }
            
            // Actualizar roles si se proporcionan
            if (isset($userData['roles']) && is_array($userData['roles'])) {
                // Eliminar roles actuales
                $deleteRolesStmt = $this->db->prepare("DELETE FROM usuario_rol WHERE id_usuario = :id_usuario");
                $deleteRolesStmt->execute([':id_usuario' => $id]);
                
                // Insertar nuevos roles
                $insertRoleStmt = $this->db->prepare("INSERT INTO usuario_rol (id_usuario, id_rol) VALUES (:id_usuario, :id_rol)");
                
                foreach ($userData['roles'] as $rolId) {
                    $insertRoleStmt->execute([
                        ':id_usuario' => $id,
                        ':id_rol' => $rolId
                    ]);
                }
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Usuario actualizado correctamente'
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            return [
                'success' => false,
                'message' => 'Error al actualizar el usuario: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Elimina un usuario
     * 
     * @param int $id ID del usuario
     * @return bool True si se eliminó, false si no
     */
    public function delete($id) {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Eliminar datos relacionados en otras tablas
            // Eliminar roles
            $stmt = $this->db->prepare("DELETE FROM usuario_rol WHERE id_usuario = :id_usuario");
            $stmt->execute([':id_usuario' => $id]);
            
            // Eliminar perfil
            $stmt = $this->db->prepare("DELETE FROM usuario_perfil WHERE id_usuario = :id_usuario");
            $stmt->execute([':id_usuario' => $id]);
            
            // Finalmente eliminar el usuario
            $stmt = $this->db->prepare("DELETE FROM usuario WHERE id_usuario = :id_usuario");
            $stmt->execute([':id_usuario' => $id]);
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Usuario eliminado correctamente'
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            return [
                'success' => false,
                'message' => 'Error al eliminar el usuario: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cambia la contraseña de un usuario
     * 
     * @param int $id ID del usuario
     * @param string $newPassword Nueva contraseña
     * @return bool True si se cambió, false si no
     */
    public function changePassword($id, $newPassword) {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, [
                'cost' => require(__DIR__ . '/../config/config.php')['security']['bcrypt_cost']
            ]);
            
            $stmt = $this->db->prepare("
                UPDATE usuario SET password_hash = :password_hash 
                WHERE id_usuario = :id_usuario
            ");
            
            $stmt->execute([
                ':password_hash' => $passwordHash,
                ':id_usuario' => $id
            ]);
            
            return [
                'success' => true,
                'message' => 'Contraseña actualizada correctamente'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al cambiar la contraseña: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verifica una cuenta de usuario
     * 
     * @param int $id ID del usuario
     * @return bool True si se verificó, false si no
     */
    public function verifyAccount($id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE usuario SET verificado = 1 
                WHERE id_usuario = :id_usuario
            ");
            
            $stmt->execute([':id_usuario' => $id]);
            
            return [
                'success' => true,
                'message' => 'Cuenta verificada correctamente'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al verificar la cuenta: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Busca usuarios según criterios
     * 
     * @param array $criteria Criterios de búsqueda
     * @param int $limit Límite de resultados
     * @param int $offset Offset para paginación
     * @return array Resultados de la búsqueda
     */
    public function search($criteria, $limit = 10, $offset = 0) {
        try {
            $conditions = [];
            $params = [];
            
            // Construir condiciones de búsqueda
            if (isset($criteria['email']) && !empty($criteria['email'])) {
                $conditions[] = "u.email LIKE :email";
                $params[':email'] = '%' . $criteria['email'] . '%';
            }
            
            if (isset($criteria['nombres']) && !empty($criteria['nombres'])) {
                $conditions[] = "p.nombres LIKE :nombres";
                $params[':nombres'] = '%' . $criteria['nombres'] . '%';
            }
            
            if (isset($criteria['apellidos']) && !empty($criteria['apellidos'])) {
                $conditions[] = "p.apellidos LIKE :apellidos";
                $params[':apellidos'] = '%' . $criteria['apellidos'] . '%';
            }
            
            if (isset($criteria['telefono']) && !empty($criteria['telefono'])) {
                $conditions[] = "p.telefono LIKE :telefono";
                $params[':telefono'] = '%' . $criteria['telefono'] . '%';
            }
            
            if (isset($criteria['verificado'])) {
                $conditions[] = "u.verificado = :verificado";
                $params[':verificado'] = $criteria['verificado'];
            }
            
            if (isset($criteria['rol_id'])) {
                $conditions[] = "EXISTS (SELECT 1 FROM usuario_rol ur WHERE ur.id_usuario = u.id_usuario AND ur.id_rol = :rol_id)";
                $params[':rol_id'] = $criteria['rol_id'];
            }
            
            // Construir la consulta SQL
            $sql = "
                SELECT u.id_usuario, u.email, u.fecha_registro, u.ultimo_acceso, u.verificado,
                       p.nombres, p.apellidos, p.telefono
                FROM usuario u
                LEFT JOIN usuario_perfil p ON u.id_usuario = p.id_usuario
            ";
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " ORDER BY u.id_usuario DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            // Bind de parámetros
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $users = [];
            while ($row = $stmt->fetch()) {
                $users[] = [
                    'id' => $row['id_usuario'],
                    'email' => $row['email'],
                    'nombres' => $row['nombres'],
                    'apellidos' => $row['apellidos'],
                    'telefono' => $row['telefono'],
                    'fecha_registro' => $row['fecha_registro'],
                    'ultimo_acceso' => $row['ultimo_acceso'],
                    'verificado' => $row['verificado']
                ];
            }
            
            // Contar total de resultados para paginación
            $countSql = "
                SELECT COUNT(*) 
                FROM usuario u
                LEFT JOIN usuario_perfil p ON u.id_usuario = p.id_usuario
            ";
            
            if (!empty($conditions)) {
                $countSql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $countStmt = $this->db->prepare($countSql);
            
            // Bind de parámetros para la consulta de conteo
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            
            $countStmt->execute();
            $total = $countStmt->fetchColumn();
            
            return [
                'users' => $users,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ];
            
        } catch (PDOException $e) {
            return [
                'users' => [],
                'total' => 0,
                'pages' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
}