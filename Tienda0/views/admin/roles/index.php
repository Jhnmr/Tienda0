<?php
// Incluir header
include_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2">
            <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Gestión de Roles</h4>
                    <?php if (userHasPermission('role_create')): ?>
                    <a href="/admin/roles/create.php" class="btn btn-light">
                        <i class="bi bi-plus-circle"></i> Nuevo Rol
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                                echo $_SESSION['error_message'];
                                unset($_SESSION['error_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                                echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tabla de roles -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Permisos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($roles)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No se encontraron roles</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($roles as $role): ?>
                                    <tr>
                                        <td><?php echo e($role['id']); ?></td>
                                        <td><?php echo e($role['nombre']); ?></td>
                                        <td><?php echo e($role['descripcion']); ?></td>
                                        <td>
                                            <?php
                                            // Obtener permisos del rol
                                            $roleDetail = $this->roleModel->getById($role['id']);
                                            $rolePermissions = $roleDetail['success'] ? count($roleDetail['role']['permissions']) : 0;
                                            echo $rolePermissions . ' permisos';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (userHasPermission('role_view')): ?>
                                            <a href="/admin/roles/show.php?id=<?php echo $role['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if (userHasPermission('role_edit')): ?>
                                            <a href="/admin/roles/edit.php?id=<?php echo $role['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if (userHasPermission('role_delete') && $role['id'] > 2): // Prevenir eliminar roles básicos ?>
                                            <a href="/admin/roles/delete.php?id=<?php echo $role['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir footer
include_once __DIR__ . '/../../../includes/footer.php';
?>