<?php
/**
 * Controlador de autenticación
 */
class authcontroller {
    private $userModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once __DIR__ . '/../models/user.php';
        require_once __DIR__ . '/../middleware/auth.php';
        require_once __DIR__ . '/../middleware/CSRF.php';
        
        $this->userModel = new user();
    }
    
    /**
     * Muestra la página de inicio de sesión
     */
    public function login() {
        // El código existente se mantiene igual
    }
    
    /**
     * Cierra la sesión
     */
    public function logout() {
        // El código existente se mantiene igual
    }
    
    /**
     * Registro de usuarios
     */
    public function register() {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect('/');
            return;
        }
        
        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                setFlash('error', 'Error de seguridad. Por favor, intente de nuevo.');
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
                'rol_id' => 2 // Cliente por defecto
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
                
                setFlash('success', 'Registro exitoso. Por favor, verifique su correo electrónico para activar su cuenta.');
                redirect('/login');
            } else {
                setFlash('error', $result['message']);
                $_SESSION['form_data'] = $_POST;
                redirect('/register');
            }
        } else {
            // Mostrar formulario de registro
            include __DIR__ . '/../views/auth/register.php';
        }
    }
    
    /**
     * Solicitud de recuperación de contraseña
     */
    public function forgotPassword() {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect('/');
            return;
        }
        
        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                setFlash('error', 'Error de seguridad. Por favor, intente de nuevo.');
                redirect('/forgotpassword');
                return;
            }
            
            // Sanitizar entrada
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            
            // Validar email
            if (empty($email) || !validateEmail($email)) {
                setFlash('error', 'Por favor, ingrese un correo electrónico válido.');
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
            setFlash('success', 'Si su correo está registrado, recibirá un email con instrucciones para restablecer su contraseña.');
            redirect('/login');
        } else {
            // Mostrar formulario
            include __DIR__ . '/../views/auth/forgotpassword.php';
        }
    }
    
    /**
     * Restablecimiento de contraseña
     */
    public function resetPassword() {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect('/');
            return;
        }
        
        // Obtener token de la URL
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            setFlash('error', 'Token de restablecimiento no válido.');
            redirect('/login');
            return;
        }
        
        // Verificar token
        $tokenInfo = $this->userModel->validateResetToken($token);
        
        if (!$tokenInfo['valid']) {
            setFlash('error', 'El token de restablecimiento no es válido o ha expirado.');
            redirect('/login');
            return;
        }
        
        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                setFlash('error', 'Error de seguridad. Por favor, intente de nuevo.');
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
                
                setFlash('success', 'Su contraseña ha sido restablecida correctamente. Ahora puede iniciar sesión.');
                redirect('/login');
            } else {
                setFlash('error', $result['message']);
                redirect('/resetpassword?token=' . $token);
            }
        } else {
            // Mostrar formulario
            include __DIR__ . '/../views/auth/resetpassword.php';
        }
    }
    
    /**
     * Perfil de usuario
     */
    public function profile() {
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            setFlash('error', 'Debe iniciar sesión para acceder a esta página.');
            redirect('/login');
            return;
        }        
        // Obtener datos del usuario
        $userId = $_SESSION['user_id'];
        $user = $this->userModel->getById($userId);
        
        if (!$user) {
            setFlash('error', 'Usuario no encontrado.');
            redirect('/');
            return;
        }
        
        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                setFlash('error', 'Error de seguridad. Por favor, intente de nuevo.');
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
                
                setFlash('success', 'Perfil actualizado correctamente.');
                redirect('/profile');
            } else {
                setFlash('error', $result['message']);
                redirect('/profile');
            }
        } else {
            // Mostrar formulario
            include __DIR__ . '/../views/auth/profile.php';
        }
    }
    
    /**
     * Cambio de contraseña
     */
    public function changePassword() {
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            setFlash('error', 'Debe iniciar sesión para acceder a esta página.');
            redirect('/login');
            return;
        }        
        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                setFlash('error', 'Error de seguridad. Por favor, intente de nuevo.');
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
                setFlash('error', 'La contraseña actual es incorrecta.');
                redirect('/changepassword');
                return;
            }
            
            // Cambiar contraseña
            $result = $this->userModel->changePassword($userId, $newPassword);
            
            if ($result['success']) {
                setFlash('success', 'Contraseña cambiada correctamente.');
                redirect('/profile');
            } else {
                setFlash('error', $result['message']);
                redirect('/changepassword');
            }
        } else {
            // Mostrar formulario
            include __DIR__ . '/../views/auth/changepassword.php';
        }
    }
    
    /**
     * Verificación de cuenta por email
     */
    public function verify() {
        // Obtener token de la URL
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            setFlash('error', 'Token de verificación no válido.');
            redirect('/login');
            return;
        }
        
        // Verificar token
        $result = $this->userModel->verifyAccount($token);
        
        if ($result['success']) {
            setFlash('success', 'Su cuenta ha sido verificada correctamente. Ahora puede iniciar sesión.');
            redirect('/login');
        } else {
            setFlash('error', $result['message']);
            redirect('/login');
        }
    }
}