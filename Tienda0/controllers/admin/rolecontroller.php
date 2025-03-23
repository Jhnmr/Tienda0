<?php

/**
 * Controlador de roles
 */
class rolecontroller
{
    private $roleModel;
    private $permissionModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        require_once __DIR__ . '/../models/role.php';
        require_once __DIR__ . '/../models/permission.php';
        require_once __DIR__ . '/../middleware/auth.php';
        require_once __DIR__ . '/../middleware/permission.php';

        // Verificar autenticación
        authMiddleware();

        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
    }

    /**
     * Lista de roles
     */
    public function index()
    {
        // Verificar permiso
        permissionMiddleware('role_view');

        // Obtener roles
        $result = $this->roleModel->getAll();
        $roles = $result['success'] ? $result['roles'] : [];

        include __DIR__ . '/../views/admin/roles/index.php';
    }

    /**
     * Formulario para crear rol
     */
    public function create()
    {
        // Verificar permiso
        permissionMiddleware('role_create');

        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('error', 'Error de seguridad. Por favor, intente de nuevo.');
                redirect('/admin/roles/create');
                return;
            }

            // Sanitizar y validar entrada
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
            $permisos = isset($_POST['permissions']) && is_array($_POST['permissions']) ?
                array_map('intval', $_POST['permissions']) : [];

            // Validar campos requeridos
            if (empty($nombre)) {
                setFlashMessage('error', 'Por favor, ingrese un nombre para el rol.');
                redirect('/admin/roles/create');
                return;
            }

            // Crear rol
            $roleData = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'permissions' => $permisos
            ];

            $result = $this->roleModel->create($roleData);

            if ($result['success']) {
                setFlashMessage('success', 'Rol creado correctamente.');
                redirect('/admin/roles');
            } else {
                setFlashMessage('error', $result['message']);
                redirect('/admin/roles/create');
            }
        } else {
            // Obtener permisos para el formulario
            $permissionsResult = $this->permissionModel->getAll();
            $permissions = $permissionsResult['success'] ? $permissionsResult['permissions'] : [];

            // Mostrar formulario
            include __DIR__ . '/../views/admin/roles/create.php';
        }
    }

    /**
     * Formulario para editar rol
     * 
     * @param int $id ID del rol
     */
    public function edit($id)
    {
        // Verificar permiso
        permissionMiddleware('role_edit');

        // Obtener rol
        $result = $this->roleModel->getById($id);

        if (!$result['success']) {
            setFlashMessage('error', $result['message']);
            redirect('/admin/roles');
            return;
        }

        $role = $result['role'];

        // Si es una solicitud POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('error', 'Error de seguridad. Por favor, intente de nuevo.');
                redirect('/admin/roles/edit/' . $id);
                return;
            }

            // Sanitizar y validar entrada
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
            $permisos = isset($_POST['permissions']) && is_array($_POST['permissions']) ?
                array_map('intval', $_POST['permissions']) : [];

            // Validar campos requeridos
            if (empty($nombre)) {
                setFlashMessage('error', 'Por favor, ingrese un nombre para el rol.');
                redirect('/admin/roles/edit/' . $id);
                return;
            }

            // Actualizar rol
            $roleData = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'permissions' => $permisos
            ];

            $result = $this->roleModel->update($id, $roleData);

            if ($result['success']) {
                setFlashMessage('success', 'Rol actualizado correctamente.');
                redirect('/admin/roles');
            } else {
                setFlashMessage('error', $result['message']);
                redirect('/admin/roles/edit/' . $id);
            }
        } else {
            // Obtener permisos para el formulario
            $permissionsResult = $this->permissionModel->getAll();
            $permissions = $permissionsResult['success'] ? $permissionsResult['permissions'] : [];

            // Obtener IDs de permisos asignados al rol
            $rolePermissions = [];
            foreach ($role['permissions'] as $permission) {
                $rolePermissions[] = $permission['id'];
            }

            // Mostrar formulario
            include __DIR__ . '/../views/admin/roles/edit.php';
        }
    }

    /**
     * Eliminar rol
     * 
     * @param int $id ID del rol
     */
    public function delete($id)
    {
        // Verificar permiso
        permissionMiddleware('role_delete');

        // Evitar eliminar roles del sistema (IDs 1 y 2)
        if ($id == 1 || $id == 2) {
            setFlashMessage('error', 'No se pueden eliminar los roles del sistema.');
            redirect('/admin/roles');
            return;
        }

        // Si es una solicitud POST, eliminar rol
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('error', 'Error de seguridad. Por favor, intente de nuevo.');
                redirect('/admin/roles');
                return;
            }

            // Eliminar rol
            $result = $this->roleModel->delete($id);

            if ($result['success']) {
                setFlashMessage('success', 'Rol eliminado correctamente.');
            } else {
                setFlashMessage('error', $result['message']);
            }

            redirect('/admin/roles');
        } else {
            // Obtener rol para confirmar eliminación
            $result = $this->roleModel->getById($id);

            if (!$result['success']) {
                setFlashMessage('error', $result['message']);
                redirect('/admin/roles');
                return;
            }

            $role = $result['role'];

            // Mostrar confirmación
            include __DIR__ . '/../views/admin/roles/delete.php';
        }
    }
}
