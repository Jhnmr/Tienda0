<?php
require_once __DIR__ . '/../models/role.php';
require_once __DIR__ . '/../models/permission.php';

class roleController {
    private $roleModel;
    private $permissionModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
    }
    
    /**
     * Lista de roles
     */
    public function index() {
        // Verificar permisos
        if (!userHasPermission('role_view')) {
            $_SESSION['error_message'] = 'No tiene permisos para acceder a esta sección.';
            redirect('/admin/dashboard.php');
        }
        
        $result = $this->roleModel->getAll();
        $roles = $result['success'] ? $result['roles'] : [];
        
        include __DIR__ . '/../views/admin/roles/index.php';
    }
    
    /**
     * Crear nuevo rol
     */
    public function create() {
        // Verificar permisos
        if (!userHasPermission('role_create')) {
            $_SESSION['error_message'] = 'No tiene permisos para crear roles.';
            redirect('/admin/roles.php');
        }
        
        // Obtener permisos para el formulario
        $permissionsResult = $this->permissionModel->getAll();
        $permissions = $permissionsResult['success'] ? $permissionsResult['permissions'] : [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/admin/roles/create.php');
            }
            
            // Recoger y validar datos
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
            $permisos = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];
            
            // Validaciones
            $errors = [];
            
            if (empty($nombre)) {
                $errors[] = 'Por favor, ingrese un nombre para el rol.';
            }
            
            // Si hay errores, mostrarlos y volver al formulario
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                $_SESSION['form_data'] = $_POST;
                redirect('/admin/roles/create.php');
            }
            
            // Crear rol
            $roleData = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'permissions' => $permisos
            ];
            
            $result = $this->roleModel->create($roleData);
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Rol creado correctamente.';
                redirect('/admin/roles.php');
            } else {
                $_SESSION['error_message'] = $result['message'];
                $_SESSION['form_data'] = $_POST;
                redirect('/admin/roles/create.php');
            }
        } else {
            // Mostrar formulario de creación
            include __DIR__ . '/../views/admin/roles/create.php';
        }
    }
    
    /**
     * Ver detalles de un rol
     */
    public function show($id) {
        // Verificar permisos
        if (!userHasPermission('role_view')) {
            $_SESSION['error_message'] = 'No tiene permisos para ver roles.';
            redirect('/admin/roles.php');
        }
        
        $result = $this->roleModel->getById($id);
        
        if (!$result['success']) {
            $_SESSION['error_message'] = $result['message'];
            redirect('/admin/roles.php');
        }
        
        $role = $result['role'];
        
        include __DIR__ . '/../views/admin/roles/show.php';
    }
    
    /**
     * Editar un rol
     */
    public function edit($id) {
        // Verificar permisos
        if (!userHasPermission('role_edit')) {
            $_SESSION['error_message'] = 'No tiene permisos para editar roles.';
            redirect('/admin/roles.php');
        }
        
        $result = $this->roleModel->getById($id);
        
        if (!$result['success']) {
            $_SESSION['error_message'] = $result['message'];
            redirect('/admin/roles.php');
        }
        
        $role = $result['role'];
        
        //// Obtener permisos para el formulario
        $permissionsResult = $this->permissionModel->getAll();
        $permissions = $permissionsResult['success'] ? $permissionsResult['permissions'] : [];
        
        // Obtener IDs de permisos asignados al rol
        $rolePermissions = [];
        foreach ($role['permissions'] as $permission) {
            $rolePermissions[] = $permission['id'];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/admin/roles/edit.php?id=' . $id);
            }
            
            // Recoger y validar datos
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
            $permisos = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];
            
            // Validaciones
            $errors = [];
            
            if (empty($nombre)) {
                $errors[] = 'Por favor, ingrese un nombre para el rol.';
            }
            
            // Si hay errores, mostrarlos y volver al formulario
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                redirect('/admin/roles/edit.php?id=' . $id);
            }
            
            // Actualizar rol
            $roleData = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'permissions' => $permisos
            ];
            
            $result = $this->roleModel->update($id, $roleData);
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Rol actualizado correctamente.';
                redirect('/admin/roles.php');
            } else {
                $_SESSION['error_message'] = $result['message'];
                redirect('/admin/roles/edit.php?id=' . $id);
            }
        } else {
            // Mostrar formulario de edición
            include __DIR__ . '/../views/admin/roles/edit.php';
        }
    }
    
    /**
     * Eliminar un rol
     */
    public function delete($id) {
        // Verificar permisos
        if (!userHasPermission('role_delete')) {
            $_SESSION['error_message'] = 'No tiene permisos para eliminar roles.';
            redirect('/admin/roles.php');
        }
        
        // Confirmar eliminación
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/admin/roles.php');
            }
            
            $result = $this->roleModel->delete($id);
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Rol eliminado correctamente.';
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            
            redirect('/admin/roles.php');
        } else {
            // Mostrar confirmación
            $result = $this->roleModel->getById($id);
            
            if (!$result['success']) {
                $_SESSION['error_message'] = $result['message'];
                redirect('/admin/roles.php');
            }
            
            $role = $result['role'];
            
            include __DIR__ . '/../views/admin/roles/delete.php';
        }
    }
}