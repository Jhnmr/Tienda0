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
                    <h4 class="mb-0">Editar Rol</h4>
                    <a href="/admin/roles.php" class="btn btn-outline-light">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
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
                    
                    <form action="/admin/roles/edit.php?id=<?php echo $role['id']; ?>" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Rol</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo e($role['nombre']); ?>" required>
                            <div class="invalid-feedback">
                                Por favor, ingrese un nombre para el rol.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo e($role['descripcion']); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Permisos</label>
                            
                            <?php
                            // Agrupar permisos por módulo
                            $permissionsByModule = [];
                            foreach ($permissions as $permission) {
                                $permissionsByModule[$permission['modulo']][] = $permission;
                            }
                            ?>
                            
                            <div class="accordion" id="permissionsAccordion">
                                <?php foreach ($permissionsByModule as $module => $modulePermissions): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo e(slugify($module)); ?>">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?php echo e(slugify($module)); ?>" 
                                                aria-expanded="true" aria-controls="collapse<?php echo e(slugify($module)); ?>">
                                            <?php echo e($module); ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo e(slugify($module)); ?>" class="accordion-collapse collapse show" 
                                         aria-labelledby="heading<?php echo e(slugify($module)); ?>" 
                                         data-bs-parent="#permissionsAccordion">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <?php foreach ($modulePermissions as $permission): ?>
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="permissions[]" value="<?php echo $permission['id']; ?>" 
                                                               id="perm<?php echo $permission['id']; ?>"
                                                               <?php echo in_array($permission['id'], $rolePermissions) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="perm<?php echo $permission['id']; ?>">
                                                            <?php echo e($permission['descripcion']); ?>
                                                            <small class="text-muted d-block"><?php echo e($permission['codigo']); ?></small>
                                                        </label>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            <a href="/admin/roles.php" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Validación de formulario Bootstrap
    (function () {
        'use strict'
        
        var forms = document.querySelectorAll('.needs-validation')
        
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>

<?php
// Incluir footer
include_once __DIR__ . '/../../../includes/footer.php';
?>