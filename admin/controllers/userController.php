<?php
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../models/role.php';

class userController {
    private $userModel;
    private $roleModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->userModel = new User();
        $this->roleModel = new Role();
    }
    
    /**
     * Lista de usuarios
     */
    public function index() {
        // Verificar permisos
        if (!userHasPermission('user_view')) {
            $_SESSION['error_message'] = 'No tiene permisos para acceder a esta sección.';
            redirect('/admin/dashboard.php');
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Filtros de búsqueda
        $searchCriteria = [];
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
            $searchCriteria['email'] = $search;
            $searchCriteria['nombres'] = $search;
            $searchCriteria['apellidos'] = $search;
        }
        
        if (isset($_GET['role']) && !empty($_GET['role'])) {
            $searchCriteria['rol_id'] = (int)$_GET['role'];
        }
        
        if (isset($_GET['verified']) && in_array($_GET['verified'], ['0', '1'])) {
            $searchCriteria['verificado'] = (int)$_GET['verified'];
        }
        
        // Obtener usuarios con filtros
        if (!empty($searchCriteria)) {
            $result = $this->userModel->search($searchCriteria, $limit, $offset);
        } else {
            $result = $this->userModel->getAll($limit, $offset);
        }
        
        // Obtener roles para el filtro
        $rolesResult = $this->roleModel->getAll();
        $roles = $rolesResult['success'] ? $rolesResult['roles'] : [];
        
        // Pasar datos a la vista
        $users = $result['users'];
        $totalUsers = $result['total'];
        $totalPages = $result['pages'];
        
        include __DIR__ . '/../views/admin/users/index.php';
    }
    
    /**
     * Crear nuevo usuario
     */
    public function create() {
        // Verificar permisos
        if (!userHasPermission('user_create')) {
            $_SESSION['error_message'] = 'No tiene permisos para crear usuarios.';
            redirect('/admin/users.php');
        }
        
        // Obtener roles para el formulario
        $rolesResult = $this->roleModel->getAll();
        $roles = $rolesResult['success'] ? $rolesResult['roles'] : [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/admin/users/create.php');
            }
            
            // Recoger y validar datos
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $nombres = filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_STRING);
            $apellidos = filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_STRING);
            $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
            $verificado = isset($_POST['verificado']) ? 1 : 0;
            $roles = isset($_POST['roles']) && is_array($_POST['roles']) ? $_POST['roles'] : [];
            
            // Validaciones
            $errors = [];
            
            if (empty($email) || !validateEmail($email)) {
                $errors[] = 'Por favor, ingrese un correo electrónico válido.';
            }
            
            if (empty($password) || !validatePassword($password)) {
                $errors[] = 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas, números y caracteres especiales.';
            }
            
            if (empty($nombres) || !validateName($nombres)) {
                $errors[] = 'Por favor, ingrese un nombre válido.';
            }
            
            if (empty($apellidos) || !validateName($apellidos)) {
                $errors[] = 'Por favor, ingrese apellidos válidos.';
            }
            
            if (!empty($telefono) && !validatePhone($telefono)) {
                $errors[] = 'Por favor, ingrese un número de teléfono válido.';
            }
            
            if (empty($roles)) {
                $errors[] = 'Por favor, seleccione al menos un rol para el usuario.';
            }
            
            // Si hay errores, mostrarlos y volver al formulario
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                $_SESSION['form_data'] = $_POST;
                redirect('/admin/users/create.php');
            }
            
            // Crear usuario
            $userData = [
                'email' => $email,
                'password' => $password,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $telefono,
                'verificado' => $verificado,
                'roles' => $roles
            ];
            
            $result = $this->userModel->create($userData);
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Usuario creado correctamente.';
                redirect('/admin/users.php');
            } else {
                $_SESSION['error_message'] = $result['message'];
                $_SESSION['form_data'] = $_POST;
                redirect('/admin/users/create.php');
            }
        } else {
            // Mostrar formulario de creación
            include __DIR__ . '/../views/admin/users/create.php';
        }
    }
    
    /**
     * Ver detalles de un usuario
     */
    public function show($id) {
        // Verificar permisos
        if (!userHasPermission('user_view')) {
            $_SESSION['error_message'] = 'No tiene permisos para ver usuarios.';
            redirect('/admin/users.php');
        }
        
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $_SESSION['error_message'] = 'Usuario no encontrado.';
            redirect('/admin/users.php');
        }
        
        include __DIR__ . '/../views/admin/users/show.php';
    }
    
    /**
     * Editar un usuario
     */
    public function edit($id) {
        // Verificar permisos
        if (!userHasPermission('user_edit')) {
            $_SESSION['error_message'] = 'No tiene permisos para editar usuarios.';
            redirect('/admin/users.php');
        }
        
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $_SESSION['error_message'] = 'Usuario no encontrado.';
            redirect('/admin/users.php');
        }
        
        // Obtener roles para el formulario
        $rolesResult = $this->roleModel->getAll();
        $roles = $rolesResult['success'] ? $rolesResult['roles'] : [];
        
        // Obtener roles actuales del usuario
        $userRoles = [];
        foreach ($user['roles'] as $role) {
            $userRoles[] = $role['id'];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/admin/users/edit.php?id=' . $id);
            }
            
            // Recoger y validar datos
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? ''; // Opcional para actualización
            $nombres = filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_STRING);
            $apellidos = filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_STRING);
            $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
            $verificado = isset($_POST['verificado']) ? 1 : 0;
            $roles = isset($_POST['roles']) && is_array($_POST['roles']) ? $_POST['roles'] : [];
            
            // Validaciones
            $errors = [];
            
            if (empty($email) || !validateEmail($email)) {
                $errors[] = 'Por favor, ingrese un correo electrónico válido.';
            }
            
            if (!empty($password) && !validatePassword($password)) {
                $errors[] = 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas, números y caracteres especiales.';
            }
            
            if (empty($nombres) || !validateName($nombres)) {
                $errors[] = 'Por favor, ingrese un nombre válido.';
            }
            
            if (empty($apellidos) || !validateName($apellidos)) {
                $errors[] = 'Por favor, ingrese apellidos válidos.';
            }
            
            if (!empty($telefono) && !validatePhone($telefono)) {
                $errors[] = 'Por favor, ingrese un número de teléfono válido.';
            }
            
            if (empty($roles)) {
                $errors[] = 'Por favor, seleccione al menos un rol para el usuario.';
            }
            
            // Si hay errores, mostrarlos y volver al formulario
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                redirect('/admin/users/edit.php?id=' . $id);
            }
            
            // Actualizar usuario
            $userData = [
                'email' => $email,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $telefono,
                'verificado' => $verificado,
                'roles' => $roles
            ];
            
            // Incluir contraseña solo si se proporcionó una nueva
            if (!empty($password)) {
                $userData['password'] = $password;
            }
            
            $result = $this->userModel->update($id, $userData);
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Usuario actualizado correctamente.';
                redirect('/admin/users.php');
            } else {
                $_SESSION['error_message'] = $result['message'];
                redirect('/admin/users/edit.php?id=' . $id);
            }
        } else {
            // Mostrar formulario de edición
            include __DIR__ . '/../views/admin/users/edit.php';
        }
    }
    
    /**
     * Eliminar un usuario
     */
    public function delete($id) {
        // Verificar permisos
        if (!userHasPermission('user_delete')) {
            $_SESSION['error_message'] = 'No tiene permisos para eliminar usuarios.';
            redirect('/admin/users.php');
        }
        
        // Confirmar eliminación
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/admin/users.php');
            }
            
            $result = $this->userModel->delete($id);
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Usuario eliminado correctamente.';
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            
            redirect('/admin/users.php');
        } else {
            // Mostrar confirmación
            $user = $this->userModel->getById($id);
            
            if (!$user) {
                $_SESSION['error_message'] = 'Usuario no encontrado.';
                redirect('/admin/users.php');
            }
            
            include __DIR__ . '/../views/admin/users/delete.php';
        }
    }
}