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
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect('/');
            return;
        }
        
        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('error', 'Error de seguridad. Por favor, intente de nuevo.');
                redirect('/login');
                return;
            }
            
            // Sanitizar entrada
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);
            
            // Validar campos requeridos
            if (empty($email) || empty($password)) {
                setFlashMessage('error', 'Por favor, complete todos los campos.');
                redirect('/login');
                return;
            }
            
            // Autenticar usuario
            $result = $this->userModel->authenticate($email, $password);
            
            if ($result['success']) {
                // Establecer sesión
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['user_email'] = $result['user']['email'];
                $_SESSION['user_name'] = $result['user']['nombres'] . ' ' . $result['user']['apellidos'];
                $_SESSION['user_roles'] = array_column($result['user']['roles'], 'id');
                $_SESSION['user_permissions'] = array_column($result['user']['permissions'], 'codigo');
                
                // Si se seleccionó "recordarme", establecer cookie
                if ($remember) {
                    $token = generateSecureToken();
                    $expiry = time() + 30 * 24 * 60 * 60; // 30 días
                    
                    // Guardar token en la base de datos
                    $this->userModel->saveRememberToken($result['user']['id'], $token, $expiry);
                    
                    // Establecer cookie
                    setcookie('remember_token', $token, $expiry, '/', '', true, true);
                }
                
                // Redireccionar según el rol
                if (userHasRole(1)) { // Administrador
                    redirect('/admin/dashboard');
                } else {
                    redirect('/');
                }
            } else {
                setFlashMessage('error', $result['message']);
                redirect('/login');
            }
        } else {
            // Mostrar formulario de inicio de sesión
            include __DIR__ . '/../views/auth/login.php';
        }
    }
    
    /**
     * Cierra la sesión
     */
    public function logout() {
        // Eliminar cookie de "recordarme" si existe
        if (isset($_COOKIE['remember_token'])) {
            // Invalidar token en la base de datos
            if (isset($_SESSION['user_id'])) {
                $this->userModel->invalidateRememberToken($_SESSION['user_id'], $_COOKIE['remember_token']);
            }
            
            // Eliminar cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        // Destruir la sesión
        session_unset();
        session_destroy();
        
        redirect('/login');
    }
    
    // Implementar los demás métodos: register, profile, forgotPassword, changePassword
    // ...
}