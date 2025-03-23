<?php
/**
 * Modelo de usuarios
 */
class user {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
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
            $config = require(__DIR__ . '/../config/config.php');
            $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT, [
                'cost' => $config['security']['bcrypt_cost']
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
            
            // Asignar rol al usuario
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
            
            // Registrar la acción en log
            $this->logActivity($userId, 'user_create', 'Usuario creado');
            
            return [
                'success' => true,
                'id' => $userId,
                'message' => 'Usuario creado correctamente'
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            // Registrar error
            error_log('Error al crear usuario: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al crear el usuario. Por favor, intente de nuevo.'
            ];
        }
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
                // No revelar si el usuario existe o no por seguridad
                return [
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ];
            }
            
            $user = $stmt->fetch();
            
            // Verificar contraseña con time-safe comparison
            if (!password_verify($password, $user['password_hash'])) {
                // Registrar intento fallido
                $this->logFailedLogin($email);
                
                return [
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ];
            }
            
            // Verificar si la cuenta está verificada
            if (!$user['verificado']) {
                return [
                    'success' => false,
                    'message' => 'La cuenta no ha sido verificada. Por favor, revise su correo.'
                ];
            }
            
            // Verificar si la cuenta está bloqueada por intentos fallidos
            if ($this->isAccountLocked($email)) {
                return [
                    'success' => false,
                    'message' => 'La cuenta ha sido bloqueada temporalmente por motivos de seguridad. Intente de nuevo más tarde.'
                ];
            }
            
            // Actualizar último acceso
            $updateStmt = $this->db->prepare("
                UPDATE usuario SET ultimo_acceso = NOW() 
                WHERE id_usuario = :id_usuario
            ");
            
            $updateStmt->execute([':id_usuario' => $user['id_usuario']]);
            
            // Restablecer contador de intentos fallidos
            $this->resetFailedLogins($email);
            
            // Obtener roles y permisos del usuario
            $roles = $this->getUserRoles($user['id_usuario']);
            $permissions = $this->getUserPermissions($user['id_usuario']);
            
            // No devolver el hash de la contraseña
            unset($user['password_hash']);
            
            // Registrar inicio de sesión exitoso
            $this->logActivity($user['id_usuario'], 'user_login', 'Inicio de sesión exitoso');
            
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
            // Registrar error
            error_log('Error al autenticar usuario: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al iniciar sesión. Por favor, intente de nuevo.'
            ];
        }
    }
    
    /**
     * Obtener usuario por ID
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
            error_log('Error al obtener usuario: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si existe un email
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
     * Obtener roles de usuario
     * 
     * @param int $userId ID del usuario
     * @return array Roles del usuario
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
     * Obtener permisos de usuario
     * 
     * @param int $userId ID del usuario
     * @return array Permisos del usuario
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
     * Guardar token de "recordarme"
     * 
     * @param int $userId ID del usuario
     * @param string $token Token generado
     * @param int $expiry Fecha de expiración (timestamp)
     * @return bool True si se guardó, false si no
     */
    public function saveRememberToken($userId, $token, $expiry) {
        try {
            // Primero eliminar tokens antiguos del usuario
            $stmt = $this->db->prepare("
                DELETE FROM user_tokens 
                WHERE id_usuario = :id_usuario AND tipo = 'remember'
            ");
            
            $stmt->execute([':id_usuario' => $userId]);
            
            // Insertar nuevo token
            $stmt = $this->db->prepare("
                INSERT INTO user_tokens (id_usuario, token, tipo, expira, creado)
                VALUES (:id_usuario, :token, 'remember', :expira, NOW())
            ");
            
            $stmt->execute([
                ':id_usuario' => $userId,
                ':token' => hash('sha256', $token), // Almacenar hash, no el token original
                ':expira' => date('Y-m-d H:i:s', $expiry)
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log('Error al guardar token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Autenticar por token de "recordarme"
     * 
     * @param string $token Token a verificar
     * @return array Resultado de la autenticación
     */
    public function authenticateByToken($token) {
        try {
            $stmt = $this->db->prepare("
                SELECT ut.id_usuario, ut.expira
                FROM user_tokens ut
                WHERE ut.token = :token AND ut.tipo = 'remember'
                LIMIT 1
            ");
            
            // Verificar con el hash del token
            $stmt->execute([':token' => hash('sha256', $token)]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Token inválido o expirado'
                ];
            }
            
            $tokenData = $stmt->fetch();
            
            // Verificar si el token ha expirado
            if (strtotime($tokenData['expira']) < time()) {
                // Eliminar token expirado
                $this->invalidateRememberToken($tokenData['id_usuario'], $token);
                
                return [
                    'success' => false,
                    'message' => 'Token expirado'
                ];
            }
            
            // Obtener datos del usuario
            $user = $this->getById($tokenData['id_usuario']);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ];
            }
            
            // Actualizar último acceso
            $updateStmt = $this->db->prepare("
                UPDATE usuario SET ultimo_acceso = NOW() 
                WHERE id_usuario = :id_usuario
            ");
            
            $updateStmt->execute([':id_usuario' => $user['id_usuario']]);
            
            // Regenerar token para seguridad
            $newToken = generateSecureToken();
            $expiry = time() + 30 * 24 * 60 * 60; // 30 días
            
            // Actualizar token
            $this->invalidateRememberToken($user['id_usuario'], $token);
            $this->saveRememberToken($user['id_usuario'], $newToken, $expiry);
            
            // Establecer nueva cookie
            setcookie('remember_token', $newToken, $expiry, '/', '', true, true);
            
            // Obtener roles y permisos
            $roles = $this->getUserRoles($user['id_usuario']);
            $permissions = $this->getUserPermissions($user['id_usuario']);
            
            // Registrar inicio de sesión
            $this->logActivity($user['id_usuario'], 'user_login', 'Inicio de sesión con token de "recordarme"');
            
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
            error_log('Error al autenticar por token: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al iniciar sesión. Por favor, intente de nuevo.'
            ];
        }
    }
    
    /**
     * Invalidar token de "recordarme"
     * 
     * @param int $userId ID del usuario
     * @param string $token Token a invalidar
     * @return bool True si se invalidó, false si no
     */
    public function invalidateRememberToken($userId, $token) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM user_tokens 
                WHERE id_usuario = :id_usuario AND token = :token AND tipo = 'remember'
            ");
            
            $stmt->execute([
                ':id_usuario' => $userId,
                ':token' => hash('sha256', $token)
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log('Error al invalidar token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar intento fallido de inicio de sesión
     * 
     * @param string $email Email del usuario
     * @return void
     */
    private function logFailedLogin($email) {
        try {
            // Crear tabla de intentos fallidos si no existe
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS failed_logins (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    ip VARCHAR(45) NOT NULL,
                    intento_fecha DATETIME NOT NULL,
                    INDEX (email)
                )
            ");
            
            $stmt = $this->db->prepare("
                INSERT INTO failed_logins (email, ip, intento_fecha)
                VALUES (:email, :ip, NOW())
            ");
            
            $stmt->execute([
                ':email' => $email,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
        } catch (PDOException $e) {
            error_log('Error al registrar intento fallido: ' . $e->getMessage());
        }
    }
    
    /**
     * Verificar si una cuenta está bloqueada por intentos fallidos
     * 
     * @param string $email Email del usuario
     * @return bool True si está bloqueada, false si no
     */
    private function isAccountLocked($email) {
        try {
            // Configuración de bloqueo
            $maxAttempts = 5; // Número máximo de intentos
            $lockoutTime = 15; // Tiempo de bloqueo en minutos
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS intentos
                FROM failed_logins
                WHERE email = :email
                AND intento_fecha > DATE_SUB(NOW(), INTERVAL :minutos MINUTE)
            ");
            
            $stmt->execute([
                ':email' => $email,
                ':minutos' => $lockoutTime
            ]);
            
            $result = $stmt->fetch();
            
            return $result['intentos'] >= $maxAttempts;
            
        } catch (PDOException $e) {
            error_log('Error al verificar bloqueo de cuenta: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Restablecer contador de intentos fallidos
     * 
     * @param string $email Email del usuario
     * @return void
     */
    private function resetFailedLogins($email) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM failed_logins
                WHERE email = :email
            ");
            
            $stmt->execute([':email' => $email]);
            
        } catch (PDOException $e) {
            error_log('Error al restablecer intentos fallidos: ' . $e->getMessage());
        }
    }
    
    /**
     * Registrar actividad de usuario
     * 
     * @param int $userId ID del usuario
     * @param string $action Acción realizada
     * @param string $details Detalles de la acción
     * @return void
     */
    private function logActivity($userId, $action, $details = '') {
        try {
            // Crear tabla de actividad si no existe
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
    
    // Implementar los demás métodos: update, delete, changePassword, etc.
}