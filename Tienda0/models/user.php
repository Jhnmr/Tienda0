<?php
class user{

    // Declarar la propiedad $db como privada
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Asegurarse de que BASEPATH está definido
    if (!defined('BASEPATH')) {
        define('BASEPATH', dirname(dirname(__FILE__)));
        define('CONFIGPATH', BASEPATH . '/config/');
    }
    
    // Incluir el archivo de base de datos con la clase Database
    require_once CONFIGPATH . 'database.php';
    
    // Inicializa la propiedad $db correctamente
    $this->db = Database::getInstance()->getConnection();
    }

/**
 * Elimina un usuario
 * 
 * @param int $id ID del usuario a eliminar
 * @return array Resultado de la operación
 */
public function delete($id) {
    try {
        // Iniciar transacción
        $this->db->beginTransaction();
        
        // Eliminar tokens asociados
        $stmt = $this->db->prepare("DELETE FROM user_tokens WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        
        // Eliminar relaciones con roles
        $stmt = $this->db->prepare("DELETE FROM usuario_rol WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        
        // Eliminar perfil
        $stmt = $this->db->prepare("DELETE FROM usuario_perfil WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        
        // Finalmente, eliminar usuario
        $stmt = $this->db->prepare("DELETE FROM usuario WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        
        // Confirmar transacción
        $this->db->commit();
        
        return [
            'success' => true,
            'message' => 'Usuario eliminado correctamente'
        ];
        
    } catch (PDOException $e) {
        // Revertir en caso de error
        $this->db->rollBack();
        
        return [
            'success' => false,
            'message' => 'Error al eliminar el usuario: ' . $e->getMessage()
        ];
    }
}

/**
 * Actualiza un usuario
 * 
 * @param int $id ID del usuario a actualizar
 * @param array $userData Datos a actualizar
 * @return array Resultado de la operación
 */
public function update($id, $userData) {
    try {
        // Iniciar transacción
        $this->db->beginTransaction();
        
        // Si se proporciona email, verificar que no exista ya (excepto para este usuario)
        if (isset($userData['email'])) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM usuario 
                WHERE email = :email AND id_usuario != :id
            ");
            
            $stmt->execute([
                ':email' => $userData['email'],
                ':id' => $id
            ]);
            
            if ($stmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'El correo electrónico ya está registrado'
                ];
            }
            
            // Actualizar email
            $stmt = $this->db->prepare("UPDATE usuario SET email = :email WHERE id_usuario = :id");
            $stmt->execute([
                ':email' => $userData['email'],
                ':id' => $id
            ]);
        }
        
        // Actualizar contraseña si se proporciona
        if (isset($userData['password'])) {
            // Hashear contraseña
            $config = require(__DIR__ . '/../config/config.php');
            $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT, [
                'cost' => $config['security']['bcrypt_cost']
            ]);
            
            $stmt = $this->db->prepare("UPDATE usuario SET password_hash = :password_hash WHERE id_usuario = :id");
            $stmt->execute([
                ':password_hash' => $passwordHash,
                ':id' => $id
            ]);
        }
        
        // Actualizar estado de verificación si se proporciona
        if (isset($userData['verificado'])) {
            $stmt = $this->db->prepare("UPDATE usuario SET verificado = :verificado WHERE id_usuario = :id");
            $stmt->execute([
                ':verificado' => $userData['verificado'],
                ':id' => $id
            ]);
        }
        
        // Actualizar datos de perfil
        if (isset($userData['nombres']) || isset($userData['apellidos']) || isset($userData['telefono'])) {
            // Primero verificar si existe el perfil
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuario_perfil WHERE id_usuario = :id");
            $stmt->execute([':id' => $id]);
            
            $profileExists = $stmt->fetchColumn() > 0;
            
            if ($profileExists) {
                // Actualizar perfil existente
                $sql = "UPDATE usuario_perfil SET ";
                $params = [':id' => $id];
                $updateFields = [];
                
                if (isset($userData['nombres'])) {
                    $updateFields[] = "nombres = :nombres";
                    $params[':nombres'] = $userData['nombres'];
                }
                
                if (isset($userData['apellidos'])) {
                    $updateFields[] = "apellidos = :apellidos";
                    $params[':apellidos'] = $userData['apellidos'];
                }
                
                if (isset($userData['telefono'])) {
                    $updateFields[] = "telefono = :telefono";
                    $params[':telefono'] = $userData['telefono'];
                }
                
                if (isset($userData['fecha_nacimiento'])) {
                    $updateFields[] = "fecha_nacimiento = :fecha_nacimiento";
                    $params[':fecha_nacimiento'] = $userData['fecha_nacimiento'];
                }
                
                if (isset($userData['genero'])) {
                    $updateFields[] = "genero = :genero";
                    $params[':genero'] = $userData['genero'];
                }
                
                if (isset($userData['marketing_consent'])) {
                    $updateFields[] = "marketing_consent = :marketing_consent";
                    $params[':marketing_consent'] = $userData['marketing_consent'];
                }
                
                $updateFields[] = "fecha_actualizacion = NOW()";
                
                if (!empty($updateFields)) {
                    $sql .= implode(', ', $updateFields) . " WHERE id_usuario = :id";
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                }
            } else {
                // Crear un nuevo perfil
                $stmt = $this->db->prepare("
                    INSERT INTO usuario_perfil (
                        id_usuario, nombres, apellidos, telefono, 
                        fecha_nacimiento, genero, marketing_consent, 
                        fecha_actualizacion
                    ) VALUES (
                        :id, :nombres, :apellidos, :telefono, 
                        :fecha_nacimiento, :genero, :marketing_consent, 
                        NOW()
                    )
                ");
                
                $stmt->execute([
                    ':id' => $id,
                    ':nombres' => $userData['nombres'] ?? '',
                    ':apellidos' => $userData['apellidos'] ?? '',
                    ':telefono' => $userData['telefono'] ?? '',
                    ':fecha_nacimiento' => $userData['fecha_nacimiento'] ?? null,
                    ':genero' => $userData['genero'] ?? '',
                    ':marketing_consent' => $userData['marketing_consent'] ?? 0
                ]);
            }
        }
        
        // Actualizar roles si se proporcionan
        if (isset($userData['roles']) && is_array($userData['roles'])) {
            // Eliminar roles actuales
            $stmt = $this->db->prepare("DELETE FROM usuario_rol WHERE id_usuario = :id");
            $stmt->execute([':id' => $id]);
            
            // Insertar nuevos roles
            $stmt = $this->db->prepare("INSERT INTO usuario_rol (id_usuario, id_rol) VALUES (:id_usuario, :id_rol)");
            
            foreach ($userData['roles'] as $rolId) {
                $stmt->execute([
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
        // Revertir en caso de error
        $this->db->rollBack();
        
        return [
            'success' => false,
            'message' => 'Error al actualizar el usuario: ' . $e->getMessage()
        ];
    }
}

/**
 * Busca usuarios según criterios
 * 
 * @param array $criteria Criterios de búsqueda
 * @param int $limit Límite de resultados
 * @param int $offset Desplazamiento
 * @return array Usuarios encontrados
 */
public function search($criteria, $limit = 10, $offset = 0) {
    try {
        // Construir la consulta base
        $sql = "
            SELECT u.id_usuario, u.email, u.verificado, u.fecha_registro, u.ultimo_acceso,
                   p.nombres, p.apellidos, p.telefono
            FROM usuario u
            LEFT JOIN usuario_perfil p ON u.id_usuario = p.id_usuario
        ";
        
        $where = [];
        $params = [];
        
        // Agregar criterios de búsqueda
        if (isset($criteria['email'])) {
            $where[] = "u.email LIKE :email";
            $params[':email'] = '%' . $criteria['email'] . '%';
        }
        
        if (isset($criteria['nombres'])) {
            $where[] = "p.nombres LIKE :nombres";
            $params[':nombres'] = '%' . $criteria['nombres'] . '%';
        }
        
        if (isset($criteria['apellidos'])) {
            $where[] = "p.apellidos LIKE :apellidos";
            $params[':apellidos'] = '%' . $criteria['apellidos'] . '%';
        }
        
        if (isset($criteria['verificado'])) {
            $where[] = "u.verificado = :verificado";
            $params[':verificado'] = $criteria['verificado'];
        }
        
        if (isset($criteria['rol_id'])) {
            $sql .= " JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario";
            $where[] = "ur.id_rol = :rol_id";
            $params[':rol_id'] = $criteria['rol_id'];
        }
        
        // Aplicar condiciones WHERE si existen
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' OR ', $where);
        }
        
        // Agregar LIMIT y OFFSET
        $sql .= " ORDER BY u.fecha_registro DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        // Consulta para contar el total
        $countSql = "
            SELECT COUNT(DISTINCT u.id_usuario) as total
            FROM usuario u
            LEFT JOIN usuario_perfil p ON u.id_usuario = p.id_usuario
        ";
        
        if (isset($criteria['rol_id'])) {
            $countSql .= " JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario";
        }
        
        if (!empty($where)) {
            $countSql .= " WHERE " . implode(' OR ', $where);
        }
        
        // Ejecutar consulta de conteo
        $countStmt = $this->db->prepare($countSql);
        
        // Bindear parámetros para el conteo (excepto limit y offset)
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $countStmt->bindValue($key, $value);
            }
        }
        
        $countStmt->execute();
        $totalUsers = $countStmt->fetchColumn();
        
        // Calcular número de páginas
        $totalPages = ceil($totalUsers / $limit);
        
        // Ejecutar consulta principal
        $stmt = $this->db->prepare($sql);
        
        // Bindear parámetros de limit y offset como enteros
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        // Bindear los demás parámetros
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        
        $users = [];
        while ($row = $stmt->fetch()) {
            $users[] = [
                'id' => $row['id_usuario'],
                'email' => $row['email'],
                'nombres' => $row['nombres'],
                'apellidos' => $row['apellidos'],
                'telefono' => $row['telefono'],
                'verificado' => $row['verificado'],
                'fecha_registro' => $row['fecha_registro'],
                'ultimo_acceso' => $row['ultimo_acceso']
            ];
        }
        
        return [
            'users' => $users,
            'total' => $totalUsers,
            'pages' => $totalPages
        ];
        
    } catch (PDOException $e) {
        error_log('Error al buscar usuarios: ' . $e->getMessage());
        
        return [
            'users' => [],
            'total' => 0,
            'pages' => 0
        ];
    }
}

/**
 * Crea un nuevo usuario
 * 
 * @param array $userData Datos del usuario
 * @return array Resultado de la operación con 'success', 'message' y 'id' si tiene éxito
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
        
        // Cargar configuración para el hash de contraseña
        $config = require CONFIGPATH . 'config.php';
        $passwordAlgorithm = $config['security']['password_algorithm'] ?? PASSWORD_ARGON2ID;
        $passwordOptions = $config['security']['password_options'] ?? [];
        
        // Hashear la contraseña
        $passwordHash = password_hash($userData['password'], $passwordAlgorithm, $passwordOptions);
        
        // Iniciar transacción
        $this->db->beginTransaction();
        
        // Insertar en la tabla usuario
        $stmt = $this->db->prepare("
            INSERT INTO usuario (
                email, 
                password_hash, 
                fecha_registro, 
                ultimo_acceso, 
                verificado, 
                id_idioma_preferido
            ) VALUES (
                :email, 
                :password_hash, 
                NOW(), 
                NOW(), 
                :verificado, 
                :id_idioma_preferido
            )
        ");
        
        $stmt->execute([
            ':email' => $userData['email'],
            ':password_hash' => $passwordHash,
            ':verificado' => $userData['verificado'] ?? 0,
            ':id_idioma_preferido' => $userData['id_idioma_preferido'] ?? 1
        ]);
        
        // Obtener el ID del usuario insertado
        $userId = $this->db->lastInsertId();
        
        // Insertar en la tabla usuario_perfil
        $stmt = $this->db->prepare("
            INSERT INTO usuario_perfil (
                id_usuario, 
                nombres, 
                apellidos, 
                telefono, 
                fecha_nacimiento, 
                genero, 
                marketing_consent, 
                fecha_actualizacion
            ) VALUES (
                :id_usuario, 
                :nombres, 
                :apellidos, 
                :telefono, 
                :fecha_nacimiento, 
                :genero, 
                :marketing_consent, 
                NOW()
            )
        ");
        
        $stmt->execute([
            ':id_usuario' => $userId,
            ':nombres' => $userData['nombres'] ?? '',
            ':apellidos' => $userData['apellidos'] ?? '',
            ':telefono' => $userData['telefono'] ?? '',
            ':fecha_nacimiento' => $userData['fecha_nacimiento'] ?? null,
            ':genero' => $userData['genero'] ?? '',
            ':marketing_consent' => $userData['marketing_consent'] ?? 0
        ]);
        
        // Asignar rol al usuario (rol cliente por defecto)
        $rolId = $userData['rol_id'] ?? 2; // 2 = Cliente por defecto
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
        
        // Registrar actividad
        $this->logActivity($userId, 'registro', 'Usuario registrado');
        
        return [
            'success' => true,
            'message' => 'Usuario creado correctamente',
            'id' => $userId
        ];
        
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        $this->db->rollBack();
        
        // Registrar error
        error_log("Error al crear usuario: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Error al crear el usuario. Por favor, intente de nuevo.'
        ];
    }
}

/**
 * Verifica si existe un email en la base de datos
 * 
 * @param string $email Email a verificar
 * @return bool True si existe, false si no
 */
public function emailExists($email) {
    $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuario WHERE email = :email");
    $stmt->execute([':email' => $email]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Guarda un token de verificación para un usuario
 * 
 * @param int $userId ID del usuario
 * @param string $token Token de verificación
 * @return bool True si se guardó correctamente, false si no
 */
public function saveVerificationToken($userId, $token) {
    try {
        // Verificar si existe la tabla de tokens
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_usuario INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                expira DATETIME NOT NULL,
                creado DATETIME NOT NULL,
                INDEX (token),
                INDEX (id_usuario, tipo)
            )
        ");
        
        // Eliminar tokens anteriores
        $stmt = $this->db->prepare("
            DELETE FROM user_tokens
            WHERE id_usuario = :id_usuario AND tipo = 'verification'
        ");
        
        $stmt->execute([':id_usuario' => $userId]);
        
        // Calcular fecha de expiración (24 horas)
        $expiry = date('Y-m-d H:i:s', time() + 86400);
        
        // Insertar nuevo token
        $stmt = $this->db->prepare("
            INSERT INTO user_tokens (id_usuario, token, tipo, expira, creado)
            VALUES (:id_usuario, :token, 'verification', :expira, NOW())
        ");
        
        $stmt->execute([
            ':id_usuario' => $userId,
            ':token' => hash('sha256', $token), // Almacenar hash, no el token original
            ':expira' => $expiry
        ]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log('Error al guardar token de verificación: ' . $e->getMessage());
        return false;
    }
}

/**
 * Registra actividad de usuario
 * 
 * @param int $userId ID del usuario
 * @param string $action Acción realizada
 * @param string $details Detalles de la acción
 * @return void
 */
private function logActivity($userId, $action, $details = '') {
    try {
        // Crear tabla si no existe
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_activity_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_usuario INT NOT NULL,
                accion VARCHAR(50) NOT NULL,
                detalles TEXT,
                ip VARCHAR(45),
                fecha DATETIME NOT NULL,
                INDEX (id_usuario),
                INDEX (accion),
                INDEX (fecha)
            )
        ");
        
        $stmt = $this->db->prepare("
            INSERT INTO user_activity_log (id_usuario, accion, detalles, ip, fecha)
            VALUES (:id_usuario, :accion, :detalles, :ip, NOW())
        ");
        
        $stmt->execute([
            ':id_usuario' => $userId,
            ':accion' => $action,
            ':detalles' => $details,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
    } catch (PDOException $e) {
        error_log('Error al registrar actividad: ' . $e->getMessage());
    }
}




/**
 * Verifica una cuenta de usuario utilizando un token de verificación
 * 
 * @param string $token Token de verificación
 * @return array Resultado de la verificación
 */
public function verifyAccount($token) {
    try {
        // Buscar el token en la base de datos
        $stmt = $this->db->prepare("
            SELECT ut.id_usuario, ut.expira
            FROM user_tokens ut
            WHERE ut.token = :token AND ut.tipo = 'verification'
            LIMIT 1
        ");
        
        // Buscar el hash del token
        $stmt->execute([':token' => hash('sha256', $token)]);
        
        if ($stmt->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Token de verificación inválido o expirado.'
            ];
        }
        
        $tokenInfo = $stmt->fetch();
        
        // Verificar si el token ha expirado
        if (strtotime($tokenInfo['expira']) < time()) {
            return [
                'success' => false,
                'message' => 'El token de verificación ha expirado.'
            ];
        }
        
        // Actualizar estado de verificación del usuario
        $updateStmt = $this->db->prepare("
            UPDATE usuario SET verificado = 1
            WHERE id_usuario = :id_usuario
        ");
        
        $updateStmt->execute([':id_usuario' => $tokenInfo['id_usuario']]);
        
        // Eliminar el token usado
        $deleteStmt = $this->db->prepare("
            DELETE FROM user_tokens
            WHERE id_usuario = :id_usuario AND tipo = 'verification'
        ");
        
        $deleteStmt->execute([':id_usuario' => $tokenInfo['id_usuario']]);
        
        // Registrar la actividad
        $this->logActivity($tokenInfo['id_usuario'], 'verificacion', 'Cuenta verificada mediante email');
        
        return [
            'success' => true,
            'message' => 'Cuenta verificada correctamente.'
        ];
        
    } catch (PDOException $e) {
        error_log('Error al verificar cuenta: ' . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Error al verificar la cuenta. Por favor, intente de nuevo.'
        ];
    }
}





/**
 * Guarda un token de restablecimiento de contraseña
 * 
 * @param string $email Email del usuario
 * @param string $token Token de restablecimiento
 * @param int $expiry Timestamp de expiración
 * @return bool True si se guardó correctamente, false si no
 */
public function saveResetToken($email, $token, $expiry) {
    try {
        // Buscar ID del usuario por email
        $stmt = $this->db->prepare("
            SELECT id_usuario FROM usuario 
            WHERE email = :email 
            LIMIT 1
        ");
        
        $stmt->execute([':email' => $email]);
        
        if ($stmt->rowCount() === 0) {
            return false;
        }
        
        $userId = $stmt->fetchColumn();
        
        // Crear tabla si no existe
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_usuario INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                expira DATETIME NOT NULL,
                creado DATETIME NOT NULL,
                INDEX (token),
                INDEX (id_usuario, tipo)
            )
        ");
        
        // Eliminar tokens anteriores
        $stmt = $this->db->prepare("
            DELETE FROM user_tokens
            WHERE id_usuario = :id_usuario AND tipo = 'reset'
        ");
        
        $stmt->execute([':id_usuario' => $userId]);
        
        // Insertar nuevo token
        $stmt = $this->db->prepare("
            INSERT INTO user_tokens (id_usuario, token, tipo, expira, creado)
            VALUES (:id_usuario, :token, 'reset', FROM_UNIXTIME(:expira), NOW())
        ");
        
        $stmt->execute([
            ':id_usuario' => $userId,
            ':token' => hash('sha256', $token), // Almacenar hash, no el token original
            ':expira' => $expiry
        ]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log('Error al guardar token de restablecimiento: ' . $e->getMessage());
        return false;
    }
}

/**
 * Valida un token de restablecimiento de contraseña
 * 
 * @param string $token Token a validar
 * @return array Resultado de la validación
 */
public function validateResetToken($token) {
    try {
        $stmt = $this->db->prepare("
            SELECT ut.id_usuario, ut.expira
            FROM user_tokens ut
            WHERE ut.token = :token AND ut.tipo = 'reset'
            LIMIT 1
        ");
        
        // Verificar con el hash del token
        $stmt->execute([':token' => hash('sha256', $token)]);
        
        if ($stmt->rowCount() === 0) {
            return [
                'valid' => false,
                'message' => 'Token de restablecimiento inválido.'
            ];
        }
        
        $tokenData = $stmt->fetch();
        
        // Verificar si el token ha expirado
        if (strtotime($tokenData['expira']) < time()) {
            return [
                'valid' => false,
                'message' => 'El token de restablecimiento ha expirado.'
            ];
        }
        
        return [
            'valid' => true,
            'user_id' => $tokenData['id_usuario']
        ];
        
    } catch (PDOException $e) {
        error_log('Error al validar token de restablecimiento: ' . $e->getMessage());
        
        return [
            'valid' => false,
            'message' => 'Error al validar el token. Por favor, intente de nuevo.'
        ];
    }
}

/**
 * Restablece la contraseña de un usuario
 * 
 * @param int $userId ID del usuario
 * @param string $newPassword Nueva contraseña (sin hashear)
 * @return array Resultado de la operación
 */
public function resetPassword($userId, $newPassword) {
    try {
        // Cargar configuración para el hash de contraseña
        $config = require CONFIGPATH . 'config.php';
        $passwordAlgorithm = $config['security']['password_algorithm'] ?? PASSWORD_ARGON2ID;
        $passwordOptions = $config['security']['password_options'] ?? [];
        
        // Hashear la nueva contraseña
        $passwordHash = password_hash($newPassword, $passwordAlgorithm, $passwordOptions);
        
        // Actualizar contraseña
        $stmt = $this->db->prepare("
            UPDATE usuario 
            SET password_hash = :password_hash
            WHERE id_usuario = :id_usuario
        ");
        
        $stmt->execute([
            ':password_hash' => $passwordHash,
            ':id_usuario' => $userId
        ]);
        
        // Registrar la actividad
        $this->logActivity($userId, 'reset_password', 'Contraseña restablecida');
        
        return [
            'success' => true,
            'message' => 'Contraseña restablecida correctamente.'
        ];
        
    } catch (PDOException $e) {
        error_log('Error al restablecer contraseña: ' . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Error al restablecer la contraseña. Por favor, intente de nuevo.'
        ];
    }
}

/**
 * Invalida todos los tokens de restablecimiento de un usuario
 * 
 * @param int $userId ID del usuario
 * @return bool True si se invalidaron correctamente, false si no
 */
public function invalidateResetTokens($userId) {
    try {
        $stmt = $this->db->prepare("
            DELETE FROM user_tokens
            WHERE id_usuario = :id_usuario AND tipo = 'reset'
        ");
        
        $stmt->execute([':id_usuario' => $userId]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log('Error al invalidar tokens de restablecimiento: ' . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene un usuario por su ID
 * 
 * @param int $id ID del usuario
 * @return array|bool Datos del usuario o false si no existe
 */
public function getById($id) {
    try {
        $stmt = $this->db->prepare("
            SELECT u.id_usuario, u.email, u.verificado, u.fecha_registro, u.ultimo_acceso,
                   p.nombres, p.apellidos, p.telefono, p.fecha_nacimiento, p.genero, p.marketing_consent
            FROM usuario u
            LEFT JOIN usuario_perfil p ON u.id_usuario = p.id_usuario
            WHERE u.id_usuario = :id
        ");
        
        $stmt->execute([':id' => $id]);
        
        if ($stmt->rowCount() === 0) {
            return false;
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener roles del usuario
        $rolesStmt = $this->db->prepare("
            SELECT r.id_rol, r.nombre
            FROM rol r
            JOIN usuario_rol ur ON r.id_rol = ur.id_rol
            WHERE ur.id_usuario = :id_usuario
        ");
        
        $rolesStmt->execute([':id_usuario' => $id]);
        
        $user['roles'] = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $user;
        
    } catch (PDOException $e) {
        error_log('Error al obtener usuario: ' . $e->getMessage());
        return false;
    }
}

/**
 * Actualiza el perfil de un usuario
 * 
 * @param int $userId ID del usuario
 * @param array $userData Datos del perfil a actualizar
 * @return array Resultado de la operación
 */
public function updateProfile($userId, $userData) {
    try {
        // Iniciar transacción
        $this->db->beginTransaction();
        
        // Verificar si existe el perfil
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuario_perfil WHERE id_usuario = :id");
        $stmt->execute([':id' => $userId]);
        
        $profileExists = $stmt->fetchColumn() > 0;
        
        if ($profileExists) {
            // Actualizar perfil existente
            $sql = "UPDATE usuario_perfil SET ";
            $params = [':id' => $userId];
            $updateFields = [];
            
            if (isset($userData['nombres'])) {
                $updateFields[] = "nombres = :nombres";
                $params[':nombres'] = $userData['nombres'];
            }
            
            if (isset($userData['apellidos'])) {
                $updateFields[] = "apellidos = :apellidos";
                $params[':apellidos'] = $userData['apellidos'];
            }
            
            if (isset($userData['telefono'])) {
                $updateFields[] = "telefono = :telefono";
                $params[':telefono'] = $userData['telefono'];
            }
            
            if (isset($userData['fecha_nacimiento'])) {
                $updateFields[] = "fecha_nacimiento = :fecha_nacimiento";
                $params[':fecha_nacimiento'] = $userData['fecha_nacimiento'];
            }
            
            if (isset($userData['genero'])) {
                $updateFields[] = "genero = :genero";
                $params[':genero'] = $userData['genero'];
            }
            
            if (isset($userData['marketing_consent'])) {
                $updateFields[] = "marketing_consent = :marketing_consent";
                $params[':marketing_consent'] = $userData['marketing_consent'];
            }
            
            $updateFields[] = "fecha_actualizacion = NOW()";
            
            if (!empty($updateFields)) {
                $sql .= implode(', ', $updateFields) . " WHERE id_usuario = :id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
        } else {
            // Crear un nuevo perfil
            $stmt = $this->db->prepare("
                INSERT INTO usuario_perfil (
                    id_usuario, nombres, apellidos, telefono, 
                    fecha_nacimiento, genero, marketing_consent, 
                    fecha_actualizacion
                ) VALUES (
                    :id, :nombres, :apellidos, :telefono, 
                    :fecha_nacimiento, :genero, :marketing_consent, 
                    NOW()
                )
            ");
            
            $stmt->execute([
                ':id' => $userId,
                ':nombres' => $userData['nombres'] ?? '',
                ':apellidos' => $userData['apellidos'] ?? '',
                ':telefono' => $userData['telefono'] ?? '',
                ':fecha_nacimiento' => $userData['fecha_nacimiento'] ?? null,
                ':genero' => $userData['genero'] ?? '',
                ':marketing_consent' => $userData['marketing_consent'] ?? 0
            ]);
        }
        
        // Si hay foto de perfil, actualizarla
        if (isset($userData['profile_photo'])) {
            // Aquí implementarías la actualización de la foto
        }
        
        // Confirmar transacción
        $this->db->commit();
        
        // Registrar actividad
        $this->logActivity($userId, 'update_profile', 'Perfil actualizado');
        
        return [
            'success' => true,
            'message' => 'Perfil actualizado correctamente'
        ];
        
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        $this->db->rollBack();
        
        error_log('Error al actualizar perfil: ' . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Error al actualizar el perfil. Por favor, intente de nuevo.'
        ];
    }
}

/**
 * Verifica la contraseña de un usuario
 * 
 * @param int $userId ID del usuario
 * @param string $password Contraseña a verificar
 * @return bool True si es correcta, false si no
 */
public function verifyPassword($userId, $password) {
    try {
        $stmt = $this->db->prepare("
            SELECT password_hash 
            FROM usuario 
            WHERE id_usuario = :id_usuario
        ");
        
        $stmt->execute([':id_usuario' => $userId]);
        
        if ($stmt->rowCount() === 0) {
            return false;
        }
        
        $hash = $stmt->fetchColumn();
        
        // Verificar contraseña
        return password_verify($password, $hash);
        
    } catch (PDOException $e) {
        error_log('Error al verificar contraseña: ' . $e->getMessage());
        return false;
    }
}

/**
 * Cambia la contraseña de un usuario
 * 
 * @param int $userId ID del usuario
 * @param string $newPassword Nueva contraseña (sin hashear)
 * @return array Resultado de la operación
 */
public function changePassword($userId, $newPassword) {
    try {
        // Cargar configuración para el hash de contraseña
        $config = require CONFIGPATH . 'config.php';
        $passwordAlgorithm = $config['security']['password_algorithm'] ?? PASSWORD_ARGON2ID;
        $passwordOptions = $config['security']['password_options'] ?? [];
        
        // Hashear la nueva contraseña
        $passwordHash = password_hash($newPassword, $passwordAlgorithm, $passwordOptions);
        
        // Actualizar contraseña
        $stmt = $this->db->prepare("
            UPDATE usuario 
            SET password_hash = :password_hash
            WHERE id_usuario = :id_usuario
        ");
        
        $stmt->execute([
            ':password_hash' => $passwordHash,
            ':id_usuario' => $userId
        ]);
        
        // Invalidar todos los tokens de recordar y restablecimiento
        $this->invalidateResetTokens($userId);
        
        // Registrar actividad
        $this->logActivity($userId, 'change_password', 'Contraseña cambiada');
        
        return [
            'success' => true,
            'message' => 'Contraseña cambiada correctamente'
        ];
        
    } catch (PDOException $e) {
        error_log('Error al cambiar contraseña: ' . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Error al cambiar la contraseña. Por favor, intente de nuevo.'
        ];
    }
}



}