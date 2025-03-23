<?php
// Incluir header
include_once __DIR__ . '/../../includes/header.php';

// Recuperar datos del formulario si hay error
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Registro de Usuario</h4>
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
                    
                    <form action="/register.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombres" class="form-label">Nombres</label>
                                <input type="text" class="form-control" id="nombres" name="nombres" 
                                       value="<?php echo e($formData['nombres'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Por favor, ingrese sus nombres.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="apellidos" class="form-label">Apellidos</label>
                                <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                       value="<?php echo e($formData['apellidos'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Por favor, ingrese sus apellidos.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo e($formData['email'] ?? ''); ?>" required>
                            <div class="invalid-feedback">
                                Por favor, ingrese un correo electrónico válido.
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">
                                    Por favor, ingrese una contraseña.
                                </div>
                                <small class="form-text text-muted">
                                    La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, 
                                    minúsculas, números y caracteres especiales.
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div class="invalid-feedback">
                                    Por favor, confirme su contraseña.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                Acepto los <a href="/terms.php" target="_blank">términos y condiciones</a>
                            </label>
                            <div class="invalid-feedback">
                                Debe aceptar los términos y condiciones para continuar.
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="marketing_consent" name="marketing_consent" 
                                  <?php echo isset($formData['marketing_consent']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="marketing_consent">
                                Deseo recibir ofertas y novedades por correo electrónico
                            </label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Registrarse</button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p>¿Ya tiene cuenta? <a href="/login.php" class="text-decoration-none">Iniciar Sesión</a></p>
                    </div>
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
                    
                    // Validación personalizada de coincidencia de contraseñas
                    const password = document.getElementById('password')
                    const confirmPassword = document.getElementById('confirm_password')
                    
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Las contraseñas no coinciden')
                        event.preventDefault()
                        event.stopPropagation()
                    } else {
                        confirmPassword.setCustomValidity('')
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>

<?php
// Incluir footer
include_once __DIR__ . '/../../includes/footer.php';
?>