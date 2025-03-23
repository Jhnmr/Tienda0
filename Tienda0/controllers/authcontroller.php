<?php

/**
 * Controlador de autenticación
 */
class authcontroller
{
    private $userModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        require_once __DIR__ . '/../models/user.php';
        require_once __DIR__ . '/../middleware/auth.php';
        require_once __DIR__ . '/../middleware/CSRF.php';

        $this->userModel = new user();
    }

    /**
     * Muestra el formulario de inicio de sesión
     */
    public function loginForm()
    {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        // Mostrar vista de inicio de sesión
        include __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Procesa el inicio de sesión
     */
    public function login()
    {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                setFlash('error_message', 'Error de seguridad. Por favor, intente de nuevo.');
                redirect('/login');
                return;
            }

            // Sanitizar entrada
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']) ? true : false;

            // Validar campos
            if (empty($email) || empty($password)) {
                setFlash('error_message', 'Por favor, complete todos los campos.');
                redirect('/login');
                return;
            }

            // Intentar iniciar sesión
            $loginResult = $this->attemptLogin($email, $password, $remember);

            if ($loginResult['success']) {
                // Redireccionar a página anterior si existe, o al dashboard
                $redirectTo = $_SESSION['redirect_after_login'] ?? '/';
                unset($_SESSION['redirect_after_login']);

                redirect($redirectTo);
            } else {
                setFlash('error_message', $loginResult['message']);
                redirect('/login');
            }
        } else {
            // Si no es POST, mostrar formulario
            $this->loginForm();
        }
    }

    /**
     * Intenta iniciar sesión con las credenciales proporcionadas
     * 
     * @param string $email Email del usuario
     * @param string $password Contraseña del usuario
     * @param bool $remember Establecer cookie de "recordar"
     * @return array Resultado del inicio de sesión
     */
    private function attemptLogin($email, $password, $remember = false)
    {
        // Verificar credenciales usando el modelo de usuario
        $user = $this->userModel->getByEmail($email);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Credenciales incorrectas.'
            ];
        }

        // Verificar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Credenciales incorrectas.'
            ];
        }

        // Verificar si la cuenta está verificada (si es requerido)
        if (!$user['verificado']) {
            return [
                'success' => false,
                'message' => 'Debe verificar su cuenta antes de iniciar sesión. Por favor, revise su correo electrónico.'
            ];
        }

        // Iniciar sesión
        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['user_email'] = $user['email'];

        // Obtener datos adicionales del usuario (nombre, roles, permisos, etc.)
        $this->loadUserData($user['id_usuario']);

        // Si se solicita "recordarme", establecer cookie
        if ($remember) {
            $this->setRememberMeCookie($user['id_usuario']);
        }

        // Registrar actividad en el log
        $this->logActivity($user['id_usuario'], 'login', 'Inicio de sesión exitoso');

        return [
            'success' => true,
            'message' => 'Inicio de sesión exitoso.'
        ];
    }

    /**
     * Carga datos adicionales del usuario en la sesión
     * 
     * @param int $userId ID del usuario
     */
    private function loadUserData($userId)
    {
        // Obtener perfil del usuario
        $profile = $this->userModel->getUserProfile($userId);

        if ($profile) {
            $_SESSION['user_name'] = $profile['nombres'] . ' ' . $profile['apellidos'];
        }

        // Cargar roles y permisos
        $this->loadUserRoles($userId);
    }

    /**
     * Carga roles y permisos del usuario
     * 
     * @param int $userId ID del usuario
     */
    private function loadUserRoles($userId)
    {
        // Obtener roles
        $roles = $this->userModel->getUserRoles($userId);

        $roleIds = [];
        $isAdmin = false;

        foreach ($roles as $role) {
            $roleIds[] = $role['id_rol'];

            // Verificar si es administrador (rol_id = 1)
            if ($role['id_rol'] == 1) {
                $isAdmin = true;
            }
        }

        $_SESSION['user_roles'] = $roleIds;
        $_SESSION['is_admin'] = $isAdmin;

        // Cargar permisos si no es admin (admin tiene todos los permisos)
        if (!$isAdmin) {
            $this->loadUserPermissions($userId);
        }
    }

    /**
     * Carga permisos del usuario
     * 
     * @param int $userId ID del usuario
     */
    private function loadUserPermissions($userId)
    {
        $permissions = $this->userModel->getUserPermissions($userId);

        $permissionCodes = [];
        foreach ($permissions as $permission) {
            $permissionCodes[] = $permission['codigo'];
        }

        $_SESSION['user_permissions'] = $permissionCodes;
    }

    /**
     * Establece cookie para la funcionalidad "Recordarme"
     * 
     * @param int $userId ID del usuario
     */
    private function setRememberMeCookie($userId)
    {
        // Generar token seguro
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));

        // Almacenar hash del validator en la base de datos
        $tokenHash = hash('sha256', $validator);
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

        // Guardar token en la base de datos
        $this->userModel->saveRememberToken($userId, $tokenHash, $expires);

        // Establecer cookie (usando la forma moderna con opciones)
        $cookieValue = $selector . ':' . $validator;
        $cookieExpires = time() + (86400 * 30); // 30 días

        // Opciones de cookie
        $cookieOptions = [
            'expires' => $cookieExpires,
            'path' => '/',
            'domain' => '',
            'secure' => false, // Cambiar a true en producción con HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        // Comprobar la versión de PHP
        if (PHP_VERSION_ID < 70300) {
            // Para PHP < 7.3, no podemos usar el parámetro de opciones
            setcookie(
                'remember_me',
                $cookieValue,
                $cookieOptions['expires'],
                $cookieOptions['path'] . '; samesite=' . $cookieOptions['samesite'],
                $cookieOptions['domain'],
                $cookieOptions['secure'],
                $cookieOptions['httponly']
            );
        } else {
            // Para PHP >= 7.3, usamos el parámetro de opciones
            setcookie('remember_me', $cookieValue, $cookieOptions);
        }
    }

    /**
     * Registra actividad en el log
     * 
     * @param int $userId ID del usuario
     * @param string $action Acción realizada
     * @param string $details Detalles adicionales
     */
    private function logActivity($userId, $action, $details = '')
    {
        // Crear un log de actividad
        $logFile = __DIR__ . '/../logs/user_activity.log';
        $logMessage = date('Y-m-d H:i:s') . ' | User ID: ' . $userId . ' | Action: ' . $action . ' | ' . $details . ' | IP: ' . $_SERVER['REMOTE_ADDR'] . PHP_EOL;

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Cierra la sesión
     */
    public function logout()
    {
        // Eliminar token de "recordarme" si existe
        if (isset($_COOKIE['remember_me'])) {
            list($selector, $validator) = explode(':', $_COOKIE['remember_me']);

            // Eliminar token de la base de datos
            $tokenHash = hash('sha256', $validator);
            $this->userModel->deleteRememberToken($tokenHash);

            // Eliminar cookie (usando la forma moderna con opciones si es posible)
            $cookieOptions = [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ];

            if (PHP_VERSION_ID < 70300) {
                setcookie(
                    'remember_me',
                    '',
                    $cookieOptions['expires'],
                    $cookieOptions['path'] . '; samesite=' . $cookieOptions['samesite'],
                    $cookieOptions['domain'],
                    $cookieOptions['secure'],
                    $cookieOptions['httponly']
                );
            } else {
                setcookie('remember_me', '', $cookieOptions);
            }
        }

        // Registrar actividad si el usuario está autenticado
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'Cierre de sesión');
        }

        // Destruir sesión
        session_destroy();

        // Redireccionar a página de inicio
        redirect('/login');
    }

    /**
     * Muestra formulario de registro
     */
    public function registerForm()
    {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        // Mostrar vista de registro
        include __DIR__ . '/../views/auth/register.php';
    }

    /**
     * Registro de usuarios
     */
    public function register()
    {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                setFlash('error_message', 'Error de seguridad. Por favor, intente de nuevo.');
                redirect('/register');
                return;
            }

            // Sanitizar entrada
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $nombres = filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_STRING);
            $apellidos = filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_STRING);
            $marketing = isset($_POST['marketing_consent']) ? 1 : 0;
            $terms = isset($_POST['terms']) ? true : false;

            // Validar campos
            $errors = [];

            if (empty($email) || !validateEmail($email)) {
                $errors[] = 'Por favor, ingrese un correo electrónico válido.';
            }

            if (empty($password) || !validatePassword($password)) {
                $errors[] = 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas, números y caracteres especiales.';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Las contraseñas no coinciden.';
            }

            if (empty($nombres)) {
                $errors[] = 'Por favor, ingrese su nombre.';
            }

            if (empty($apellidos)) {
                $errors[] = 'Por favor, ingrese sus apellidos.';
            }

            if (!$terms) {
                $errors[] = 'Debe aceptar los términos y condiciones para continuar.';
            }

            // Verificar si el email ya existe
            if (empty($errors) && $this->userModel->emailExists($email)) {
                $errors[] = 'El correo electrónico ya está registrado.';
            }

            // Si hay errores, mostrarlos y volver al formulario
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                $_SESSION['form_data'] = $_POST;
                redirect('/register');
                return;
            }

            // Crear usuario
            $userData = [
                'email' => $email,
                'password' => $password,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'marketing_consent' => $marketing,
                'verificado' => 0, // Requerirá verificación por email
                'rol_id' => 3 // Cliente por defecto
            ];

            $result = $this->userModel->create($userData);

            if ($result['success']) {
                // Generar token de verificación
                $userId = $result['id'];
                $verificationToken = bin2hex(random_bytes(32));

                // Guardar token en la base de datos
                $this->userModel->saveVerificationToken($userId, $verificationToken);

                // Aquí se implementaría el envío del email con el token
                // Usar servicio de email que implementarás más adelante

                setFlash('success_message', 'Registro exitoso. Por favor, verifique su correo electrónico para activar su cuenta.');
                redirect('/login');
            } else {
                setFlash('error_message', $result['message']);
                $_SESSION['form_data'] = $_POST;
                redirect('/register');
            }
        } else {
            // Mostrar formulario de registro
            $this->registerForm();
        }
    }

    /**
     * Muestra formulario de recuperación de contraseña
     */
    public function forgotPasswordForm()
    {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        // Mostrar vista de recuperación de contraseña
        include __DIR__ . '/../views/auth/forgotpassword.php';
    }

    /**
     * Procesa solicitud de recuperación de contraseña
     */
    public function forgotPassword()
    {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                setFlash('error_message', 'Error de seguridad. Por favor, intente de nuevo.');
                redirect('/forgotpassword');
                return;
            }

            // Sanitizar entrada
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

            // Validar email
            if (empty($email) || !validateEmail($email)) {
                setFlash('error_message', 'Por favor, ingrese un correo electrónico válido.');
                redirect('/forgotpassword');
                return;
            }

            // Verificar si el email existe
            if ($this->userModel->emailExists($email)) {
                // Generar token de restablecimiento
                $resetToken = bin2hex(random_bytes(32));
                $expiry = time() + 3600; // 1 hora

                // Guardar token en la base de datos
                $this->userModel->saveResetToken($email, $resetToken, $expiry);

                // Enviar email con el token
                // $this->sendResetEmail($email, $resetToken);
            }

            // Por seguridad, siempre mostrar el mismo mensaje, independientemente de si el email existe o no
            setFlash('success_message', 'Si su correo está registrado, recibirá un email con instrucciones para restablecer su contraseña.');
            redirect('/login');
        } else {
            // Mostrar formulario
            $this->forgotPasswordForm();
        }
    }

    /**
     * Muestra formulario de restablecimiento de contraseña
     */
    public function resetPasswordForm()
    {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        // Obtener token de la URL
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            setFlash('error_message', 'Token de restablecimiento no válido.');
            redirect('/login');
            return;
        }

        // Verificar token
        $tokenInfo = $this->userModel->validateResetToken($token);

        if (!$tokenInfo['valid']) {
            setFlash('error_message', 'El token de restablecimiento no es válido o ha expirado.');
            redirect('/login');
            return;
        }

        // Mostrar formulario de restablecimiento
        include __DIR__ . '/../views/auth/resetpassword.php';
    }

    /**
     * Procesa restablecimiento de contraseña
     */
    public function resetPassword()
    {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        // Obtener token de la URL
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            setFlash('error_message', 'Token de restablecimiento no válido.');
            redirect('/login');
            return;
        }

        // Verificar token
        $tokenInfo = $this->userModel->validateResetToken($token);

        if (!$tokenInfo['valid']) {
            setFlash('error_message', 'El token de restablecimiento no es válido o ha expirado.');
            redirect('/login');
            return;
        }

        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                setFlash('error_message', 'Error de seguridad. Por favor, intente de nuevo.');
                redirect('/resetpassword?token=' . $token);
                return;
            }

            // Sanitizar y validar entrada
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validaciones
            $errors = [];

            if (empty($password) || !validatePassword($password)) {
                $errors[] = 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas, números y caracteres especiales.';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Las contraseñas no coinciden.';
            }

            // Si hay errores, mostrarlos y volver al formulario
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                redirect('/resetpassword?token=' . $token);
                return;
            }

            // Actualizar contraseña
            $result = $this->userModel->resetPassword($tokenInfo['user_id'], $password);

            if ($result['success']) {
                // Invalidar todos los tokens de reset para este usuario
                $this->userModel->invalidateResetTokens($tokenInfo['user_id']);

                setFlash('success_message', 'Su contraseña ha sido restablecida correctamente. Ahora puede iniciar sesión.');
                redirect('/login');
            } else {
                setFlash('error_message', $result['message']);
                redirect('/resetpassword?token=' . $token);
            }
        } else {
            // Mostrar formulario
            $this->resetPasswordForm();
        }
    }

    /**
     * Muestra perfil de usuario
     */
    public function profile()
    {
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            setFlash('error_message', 'Debe iniciar sesión para acceder a esta página.');
            redirect('/login');
            return;
        }

        // Obtener datos del usuario
        $userId = $_SESSION['user_id'];
        $user = $this->userModel->getById($userId);

        if (!$user) {
            setFlash('error_message', 'Usuario no encontrado.');
            redirect('/');
            return;
        }

        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                setFlash('error_message', 'Error de seguridad. Por favor, intente de nuevo.');
                redirect('/profile');
                return;
            }

            // Sanitizar entrada
            $nombres = filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_STRING);
            $apellidos = filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_STRING);
            $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
            $fechaNacimiento = filter_input(INPUT_POST, 'fecha_nacimiento', FILTER_SANITIZE_STRING);
            $genero = filter_input(INPUT_POST, 'genero', FILTER_SANITIZE_STRING);
            $marketing = isset($_POST['marketing_consent']) ? 1 : 0;

            // Validaciones
            $errors = [];

            if (empty($nombres)) {
                $errors[] = 'Por favor, ingrese su nombre.';
            }

            if (empty($apellidos)) {
                $errors[] = 'Por favor, ingrese sus apellidos.';
            }

            if (!empty($telefono) && !validatePhone($telefono)) {
                $errors[] = 'Por favor, ingrese un número de teléfono válido.';
            }

            if (!empty($fechaNacimiento) && !validateDate($fechaNacimiento)) {
                $errors[] = 'Por favor, ingrese una fecha de nacimiento válida.';
            }

            // Si hay errores, mostrarlos y volver al formulario
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                redirect('/profile');
                return;
            }

            // Actualizar perfil
            $userData = [
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $telefono,
                'fecha_nacimiento' => $fechaNacimiento,
                'genero' => $genero,
                'marketing_consent' => $marketing
            ];

            // Procesar imagen de perfil si se subió
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $validationResult = validateImage($_FILES['profile_photo']);

                if (!$validationResult['valid']) {
                    $_SESSION['error_message'] = $validationResult['message'];
                    redirect('/profile');
                    return;
                }

                // Subir y procesar la imagen
                // $uploadResult = uploadProfileImage($_FILES['profile_photo'], $userId);
                // if ($uploadResult['success']) {
                //     $userData['profile_photo'] = $uploadResult['path'];
                // }
            }

            $result = $this->userModel->updateProfile($userId, $userData);

            if ($result['success']) {
                // Actualizar nombre en la sesión
                $_SESSION['user_name'] = $nombres . ' ' . $apellidos;

                setFlash('success_message', 'Perfil actualizado correctamente.');
                redirect('/profile');
            } else {
                setFlash('error_message', $result['message']);
                redirect('/profile');
            }
        } else {
            // Mostrar formulario
            include __DIR__ . '/../views/auth/profile.php';
        }
    }

    /**
     * Muestra formulario para cambiar contraseña
     */
    public function changePasswordForm()
    {
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            setFlash('error_message', 'Debe iniciar sesión para acceder a esta página.');
            redirect('/login');
            return;
        }

        // Mostrar formulario
        include __DIR__ . '/../views/auth/changepassword.php';
    }

    /**
     * Procesa cambio de contraseña
     */
    public function changePassword()
    {
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            setFlash('error_message', 'Debe iniciar sesión para acceder a esta página.');
            redirect('/login');
            return;
        }

        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                setFlash('error_message', 'Error de seguridad. Por favor, intente de nuevo.');
                redirect('/changepassword');
                return;
            }

            // Sanitizar entrada
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validaciones
            $errors = [];

            if (empty($currentPassword)) {
                $errors[] = 'Por favor, ingrese su contraseña actual.';
            }

            if (empty($newPassword) || !validatePassword($newPassword)) {
                $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas, números y caracteres especiales.';
            }

            if ($newPassword !== $confirmPassword) {
                $errors[] = 'Las contraseñas nuevas no coinciden.';
            }

            // Si hay errores, mostrarlos y volver al formulario
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                redirect('/changepassword');
                return;
            }

            // Verificar contraseña actual
            $userId = $_SESSION['user_id'];

            if (!$this->userModel->verifyPassword($userId, $currentPassword)) {
                setFlash('error_message', 'La contraseña actual es incorrecta.');
                redirect('/changepassword');
                return;
            }

            // Cambiar contraseña
            $result = $this->userModel->changePassword($userId, $newPassword);

            if ($result['success']) {
                setFlash('success_message', 'Contraseña cambiada correctamente.');
                redirect('/profile');
            } else {
                setFlash('error_message', $result['message']);
                redirect('/changepassword');
            }
        } else {
            // Mostrar formulario
            $this->changePasswordForm();
        }
    }

    /**
     * Verificación de cuenta por email
     */
    public function verify()
    {
        // Obtener token de la URL
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            setFlash('error_message', 'Token de verificación no válido.');
            redirect('/login');
            return;
        }

        // Verificar token
        $result = $this->userModel->verifyAccount($token);

        if ($result['success']) {
            setFlash('success_message', 'Su cuenta ha sido verificada correctamente. Ahora puede iniciar sesión.');
            redirect('/login');
        } else {
            setFlash('error_message', $result['message']);
            redirect('/login');
        }
    }
}
