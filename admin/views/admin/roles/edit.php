<?php
// views/admin/roles/edit.php
// Vista para crear o editar un rol
include_once __DIR__ . '/../../../includes/header.php';

// Variables para edición o creación
$roleName = isset($role['nombre']) ? $role['nombre'] : '';
$roleDescription = isset($role['descripcion']) ? $role['descripcion'] : '';
$action = isset($role['id_rol']) ? "Editar Rol" : "Crear Rol";
?>
<div class="container mt-4">
    <h2><?php echo $action; ?></h2>
    <form action="/admin/roles/process.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <?php if(isset($role['id_rol'])): ?>
            <input type="hidden" name="id_rol" value="<?php echo $role['id_rol']; ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($roleName); ?>" required>
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" required><?php echo htmlspecialchars($roleDescription); ?></textarea>
        </div>
        <button type="submit" class="btn btn-success">Guardar</button>
    </form>
</div>
<?php include_once __DIR__ . '/../../../includes/footer.php'; ?>
