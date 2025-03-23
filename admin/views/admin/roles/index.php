<?php
// views/admin/roles/index.php
// Vista para listar y gestionar roles
include_once __DIR__ . '/../../../includes/header.php';
?>
<div class="container mt-4">
    <h2>Gestión de Roles</h2>
    <a href="/admin/roles/edit.php" class="btn btn-primary mb-3">Nuevo Rol</a>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($roles as $role): ?>
            <tr>
                <td><?php echo htmlspecialchars($role['id_rol']); ?></td>
                <td><?php echo htmlspecialchars($role['nombre']); ?></td>
                <td><?php echo htmlspecialchars($role['descripcion']); ?></td>
                <td>
                    <a href="/admin/roles/edit.php?id=<?php echo $role['id_rol']; ?>" class="btn btn-sm btn-primary">Editar</a>
                    <a href="/admin/roles/delete.php?id=<?php echo $role['id_rol']; ?>" class="btn btn-sm btn-danger">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include_once __DIR__ . '/../../../includes/footer.php'; ?>
