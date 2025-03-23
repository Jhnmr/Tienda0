<?php
require_once __DIR__ . '/includes/middleware.php';
require_once __DIR__ . '/../models/user.php';

class authController
{
    private $userModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Maneja el inicio de sesión
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/login.php');
            }

            // Validar datos de entrada
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $_SESSION['error_message'] = 'Por favor, complete todos los campos.';
                redirect('/login.php');
            }

            if (!validateEmail($email)) {
                $_SESSION['error_message'] = 'Por favor, ingrese un correo electrónico válido.';
                redirect('/login.php');
            }

            // Autenticar usuario
            $result = $this->userModel->authenticate($email, $password);

            if ($result['success']) {
                // Iniciar sesión
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['user_email'] = $result['user']['email'];
                $_SESSION['user_name'] = $result['user']['nombres'] . ' ' . $result['user']['apellidos'];

                // Guardar roles y permisos en la sesión
                $roleIds = [];
                $permissionCodes = [];

                foreach ($result['user']['roles'] as $role) {
                    $roleIds[] = $role['id'];
                }

                foreach ($result['user']['permissions'] as $permission) {
                    $permissionCodes[] = $permission['codigo'];
                }

                $_SESSION['user_roles'] = $roleIds;
                $_SESSION['user_permissions'] = $permissionCodes;

                // Registrar inicio de sesión
                // Aquí se podría implementar un registro de accesos

                // Redireccionar según rol
                if (userHasRole(1)) { // 1 = Administrador (ajustar según tu estructura)
                    redirect('/admin/dashboard.php');
                } else {
                    redirect('/index.php');
                }
            } else {
                $_SESSION['error_message'] = $result['message'];
                redirect('/login.php');
            }
        } else {
            // Si no es POST, mostrar formulario de login
            include __DIR__ . '/../views/auth/login.php';
        }
    }

    /**
     * Maneja el registro de usuarios
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/register.php');
            }

            // Validar datos de entrada
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $nombres = filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_STRING);
            $apellidos = filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_STRING);

            // Validaciones básicas
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

            if (empty($nombres) || !validateName($nombres) || mb_strlen($nombres) < 2) {
                $errors[] = 'Por favor, ingrese un nombre válido (solo letras y al menos 2 caracteres).';
            }

            if (empty($apellidos) || !validateName($apellidos) || mb_strlen($apellidos) < 2) {
                $errors[] = 'Por favor, ingrese apellidos válidos (solo letras y al menos 2 caracteres).';
            }

            // Si hay errores, mostrarlos y volver al formulario
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                $_SESSION['form_data'] = [
                    'email' => $email,
                    'nombres' => $nombres,
                    'apellidos' => $apellidos
                ];
                redirect('/register.php');
            }

            // Crear usuario
            $userData = [
                'email' => $email,
                'password' => $password,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'verificado' => 1, // En un entorno real, sería 0 y se enviaría un email de verificación
                'rol_id' => 2 // 2 = Cliente (ajustar según tu estructura)
            ];

            $result = $this->userModel->create($userData);

            if ($result['success']) {
                $_SESSION['success_message'] = 'Registro exitoso. Ahora puede iniciar sesión.';
                redirect('/login.php');
            } else {
                $_SESSION['error_message'] = $result['message'];
                $_SESSION['form_data'] = [
                    'email' => $email,
                    'nombres' => $nombres,
                    'apellidos' => $apellidos
                ];
                redirect('/register.php');
            }
        } else {
            // Si no es POST, mostrar formulario de registro
            include __DIR__ . '/../views/auth/register.php';
        }
    }

    /**
     * Cierra la sesión
     */
    public function logout()
    {
        // Destruir todas las variables de sesión
        $_SESSION = [];

        // Si se desea destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Finalmente, destruir la sesión
        session_destroy();

        redirect('/login.php');
    }

    /**
     * Maneja la recuperación de contraseña
     */
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/forgot-password.php');
            }

            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

            if (empty($email) || !validateEmail($email)) {
                $_SESSION['error_message'] = 'Por favor, ingrese un correo electrónico válido.';
                redirect('/forgot-password.php');
            }

            // En un sistema real, aquí se generaría un token único, se guardaría en la base de datos
            // y se enviaría un correo electrónico con un enlace para restablecer la contraseña

            $_SESSION['success_message'] = 'Si el correo existe en nuestra base de datos, recibirá un enlace para restablecer su contraseña.';
            redirect('/login.php');
        } else {
            // Si no es POST, mostrar formulario de recuperación
            include __DIR__ . '/../views/auth/forgot_password.php';
        }
    }

    /**
     * Página de perfil del usuario
     */
    public function profile()
    {
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            redirect('/login.php');
        }

        // Obtener datos del usuario
        $userId = $_SESSION['user_id'];
        $user = $this->userModel->getById($userId);

        if (!$user) {
            $_SESSION['error_message'] = 'No se encontró la información del usuario.';
            redirect('/index.php');
        }

        // Si es una solicitud POST, actualizar perfil
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/profile.php');
            }

            // Recoger y validar datos
            $nombres = filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_STRING);
            $apellidos = filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_STRING);
            $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
            $fechaNacimiento = filter_input(INPUT_POST, 'fecha_nacimiento', FILTER_SANITIZE_STRING);
            $genero = filter_input(INPUT_POST, 'genero', FILTER_SANITIZE_STRING);
            $marketingConsent = isset($_POST['marketing_consent']) ? 1 : 0;

            // Validaciones
            $errors = [];

            if (empty($nombres) || !validateName($nombres)) {
                $errors[] = 'Por favor, ingrese un nombre válido.';
            }

            if (empty($apellidos) || !validateName($apellidos)) {
                $errors[] = 'Por favor, ingrese apellidos válidos.';
            }

            if (!empty($telefono) && !validatePhone($telefono)) {
                $errors[] = 'Por favor, ingrese un número de teléfono válido.';
            }

            if (!empty($fechaNacimiento) && !validateDate($fechaNacimiento)) {
                $errors[] = 'Por favor, ingrese una fecha de nacimiento válida.';
            }

            if (!empty($genero) && !validateInArray($genero, ['M', 'F', 'O'])) {
                $errors[] = 'Por favor, seleccione un género válido.';
            }

            // Si hay errores, mostrarlos y volver al formulario
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                redirect('/profile.php');
            }

            // Actualizar datos del usuario
            $userData = [
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $telefono,
                'fecha_nacimiento' => $fechaNacimiento,
                'genero' => $genero,
                'marketing_consent' => $marketingConsent
            ];

            // Procesar foto de perfil si se subió una nueva
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['size'] > 0) {
                $photoValidation = validateImage($_FILES['profile_photo']);

                if (!$photoValidation['valid']) {
                    $_SESSION['error_message'] = $photoValidation['message'];
                    redirect('/profile.php');
                }

                // En un sistema real, aquí se procesaría y guardaría la imagen
                // $userData['profile_photo'] = $uploadedPhotoPath;
            }

            $result = $this->userModel->update($userId, $userData);

            if ($result['success']) {
                $_SESSION['success_message'] = 'Perfil actualizado correctamente.';
                // Actualizar nombre en la sesión
                $_SESSION['user_name'] = $nombres . ' ' . $apellidos;
                redirect('/profile.php');
            } else {
                $_SESSION['error_message'] = $result['message'];
                redirect('/profile.php');
            }
        }

        // Mostrar vista de perfil
        include __DIR__ . '/../views/auth/profile.php';
    }

    /**
     * Cambio de contraseña
     */
    public function changePassword()
    {
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            redirect('/login.php');
        }

        $userId = $_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/change-password.php');
            }

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
                $errors[] = 'Las contraseñas no coinciden.';
            }

            // Si hay errores, mostrarlos y volver al formulario
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                redirect('/change-password.php');
            }

            // Verificar contraseña actual y actualizar
            // En un sistema real, aquí verificaríamos la contraseña actual
            // contra la almacenada en la base de datos

            $result = $this->userModel->changePassword($userId, $newPassword);

            if ($result['success']) {
                $_SESSION['success_message'] = 'Contraseña actualizada correctamente.';
                redirect('/profile.php');
            } else {
                $_SESSION['error_message'] = $result['message'];
                redirect('/change-password.php');
            }
        } else {
            // Mostrar formulario de cambio de contraseña
            include __DIR__ . '/../views/auth/change_password.php';
        }
    }
}
