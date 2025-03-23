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
                    <h4 class="mb-0">Gestión de Usuarios</h4>
                    <?php if (userHasPermission('user_create')): ?>
                    <a href="/admin/users/create.php" class="btn btn-light">
                        <i class="bi bi-plus-circle"></i> Nuevo Usuario
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
                    
                    <!-- Filtros de búsqueda -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form action="/admin/users.php" method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="search" placeholder="Buscar por email, nombre..." 
                                           value="<?php echo isset($_GET['search']) ? e($_GET['search']) : ''; ?>">
                                </div>
                                <div class="col-md-3">
                                    <select name="role" class="form-select">
                                        <option value="">Todos los roles</option>
                                        <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['id']; ?>" <?php echo (isset($_GET['role']) && $_GET['role'] == $role['id']) ? 'selected' : ''; ?>>
                                            <?php echo e($role['nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="verified" class="form-select">
                                        <option value="">Todos los estados</option>
                                        <option value="1" <?php echo (isset($_GET['verified']) && $_GET['verified'] == '1') ? 'selected' : ''; ?>>Verificados</option>
                                        <option value="0" <?php echo (isset($_GET['verified']) && $_GET['verified'] == '0') ? 'selected' : ''; ?>>No verificados</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Tabla de usuarios -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Registro</th>
                                    <th>Verificado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No se encontraron usuarios</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo e($user['id']); ?></td>
                                        <td><?php echo e($user['nombres'] . ' ' . $user['apellidos']); ?></td>
                                        <td><?php echo e($user['email']); ?></td>
                                        <td><?php echo e($user['telefono'] ?? '-'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($user['fecha_registro'])); ?></td>
                                        <td>
                                            <?php if ($user['verificado']): ?>
                                                <span class="badge bg-success">Sí</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (userHasPermission('user_view')): ?>
                                            <a href="/admin/users/show.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if (userHasPermission('user_edit')): ?>
                                            <a href="/admin/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if (userHasPermission('user_delete')): ?>
                                            <a href="/admin/users/delete.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar">
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
                    
                    <!-- Paginación -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php 
                            $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                            $queryParams = $_GET;
                            unset($queryParams['page']);
                            $queryString = http_build_query($queryParams);
                            $queryString = !empty($queryString) ? '&' . $queryString : '';
                            ?>
                            
                            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $currentPage - 1 . $queryString; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $currentPage == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i . $queryString; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $currentPage + 1 . $queryString; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir footer
include_once __DIR__ . '/../../../includes/footer.php';
?>
