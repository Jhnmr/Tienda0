<?php
// Incluir header
include_once __DIR__ . '/../components/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Mi Perfil</h4>
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
                    
                    <form action="/profile.php" method="POST" class="needs-validation" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-3 text-center mb-4">
                                <div class="mb-3">
                                    <img src="<?php echo isset($user['profile_photo']) ? e($user['profile_photo']) : '/assets/img/default-avatar.png'; ?>" 
                                         alt="Foto de perfil" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                                </div>
                                <div class="mb-3">
                                    <label for="profile_photo" class="form-label">Cambiar foto</label>
                                    <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                                    <small class="form-text text-muted">
                                        Máximo 2MB, formatos: JPG, PNG o GIF.
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-9">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nombres" class="form-label">Nombres</label>
                                        <input type="text" class="form-control" id="nombres" name="nombres" 
                                               value="<?php echo e($user['nombres']); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor, ingrese sus nombres.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="apellidos" class="form-label">Apellidos</label>
                                        <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                               value="<?php echo e($user['apellidos']); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor, ingrese sus apellidos.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo e($user['email']); ?>" readonly>
                                    <small class="form-text text-muted">
                                        El correo electrónico no se puede modificar.
                                    </small>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                                               value="<?php echo e($user['telefono'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                                               value="<?php echo e($user['fecha_nacimiento'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="genero" class="form-label">Género</label>
                                    <select class="form-select" id="genero" name="genero">
                                        <option value="">Seleccionar...</option>
                                        <option value="M" <?php echo ($user['genero'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="F" <?php echo ($user['genero'] ?? '') === 'F' ? 'selected' : ''; ?>>Femenino</option>
                                        <option value="O" <?php echo ($user['genero'] ?? '') === 'O' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="marketing_consent" name="marketing_consent" 
                                          <?php echo (isset($user['marketing_consent']) && $user['marketing_consent'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="marketing_consent">
                                        Deseo recibir ofertas y novedades por correo electrónico
                                    </label>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                    <a href="/change-password.php" class="btn btn-outline-secondary">Cambiar Contraseña</a>
                                </div>
                            </div>
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
        
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation')
        
        // Loop over them and prevent submission
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
include_once __DIR__ . '/../components/footer.php';
?>