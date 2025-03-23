<?php
// views/admin/users/edit.php
// Vista para crear o editar un usuario
include_once __DIR__ . '/../../../includes/header.php';

// Variables para edición o creación
$email = isset($user['email']) ? $user['email'] : '';
$nombres = isset($user['nombres']) ? $user['nombres'] : '';
$apellidos = isset($user['apellidos']) ? $user['apellidos'] : '';
$action = isset($user['id_usuario']) ? "Editar Usuario" : "Crear Usuario";
?>
<div class="container mt-4">
    <h2><?php echo $action; ?></h2>
    <form action="/admin/users/process.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <?php if(isset($user['id_usuario'])): ?>
            <input type="hidden" name="id_usuario" value="<?php echo $user['id_usuario']; ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="mb-3">
            <label for="nombres" class="form-label">Nombres</label>
            <input type="text" class="form-control" id="nombres" name="nombres" value="<?php echo htmlspecialchars($nombres); ?>" required>
        </div>
        <div class="mb-3">
            <label for="apellidos" class="form-label">Apellidos</label>
            <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?php echo htmlspecialchars($apellidos); ?>" required>
        </div>
        <!-- Agregar otros campos según sea necesario -->
        <button type="submit" class="btn btn-success">Guardar</button>
    </form>
</div>
<?php include_once __DIR__ . '/../../../includes/footer.php'; ?>
