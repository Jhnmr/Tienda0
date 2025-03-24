<?php
// Definir título de la página
$pageTitle = 'Registro de Usuario';

// Incluir header
include_once __DIR__ . '/../../views/components/header.php';

// Recuperar datos del formulario si hay error
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>

<section class="section">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-two-thirds">
                <div class="box">
                    <h1 class="title has-text-centered has-text-primary">Registro de Usuario</h1>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="notification is-danger is-light">
                            <button class="delete"></button>
                            <?php 
                                echo $_SESSION['error_message'];
                                unset($_SESSION['error_message']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="/register" method="POST" id="register-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="columns">
                            <div class="column">
                                <div class="field">
                                    <label class="label" for="nombres">Nombres</label>
                                    <div class="control has-icons-left">
                                        <input class="input" type="text" id="nombres" name="nombres" 
                                               value="<?php echo e($formData['nombres'] ?? ''); ?>" required>
                                        <span class="icon is-small is-left">
                                            <i class="fas fa-user"></i>
                                        </span>
                                    </div>
                                    <p class="help is-danger nombres-error" style="display: none;">Por favor, ingrese sus nombres.</p>
                                </div>
                            </div>
                            
                            <div class="column">
                                <div class="field">
                                    <label class="label" for="apellidos">Apellidos</label>
                                    <div class="control has-icons-left">
                                        <input class="input" type="text" id="apellidos" name="apellidos" 
                                               value="<?php echo e($formData['apellidos'] ?? ''); ?>" required>
                                        <span class="icon is-small is-left">
                                            <i class="fas fa-user"></i>
                                        </span>
                                    </div>
                                    <p class="help is-danger apellidos-error" style="display: none;">Por favor, ingrese sus apellidos.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="field">
                            <label class="label" for="email">Correo Electrónico</label>
                            <div class="control has-icons-left">
                                <input class="input" type="email" id="email" name="email" 
                                       value="<?php echo e($formData['email'] ?? ''); ?>" required>
                                <span class="icon is-small is-left">
                                    <i class="fas fa-envelope"></i>
                                </span>
                            </div>
                            <p class="help is-danger email-error" style="display: none;">Por favor, ingrese un correo electrónico válido.</p>
                        </div>
                        
                        <div class="columns">
                            <div class="column">
                                <div class="field">
                                    <label class="label" for="password">Contraseña</label>
                                    <div class="control has-icons-left">
                                        <input class="input" type="password" id="password" name="password" required>
                                        <span class="icon is-small is-left">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    </div>
                                    <p class="help is-danger password-error" style="display: none;">La contraseña debe tener al menos 8 caracteres.</p>
                                    <p class="help">
                                        La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, 
                                        minúsculas, números y caracteres especiales.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="column">
                                <div class="field">
                                    <label class="label" for="confirm_password">Confirmar Contraseña</label>
                                    <div class="control has-icons-left">
                                        <input class="input" type="password" id="confirm_password" name="confirm_password" required>
                                        <span class="icon is-small is-left">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    </div>
                                    <p class="help is-danger confirm-password-error" style="display: none;">Las contraseñas no coinciden.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="field">
                            <div class="control">
                                <label class="checkbox">
                                    <input type="checkbox" id="terms" name="terms" required>
                                    Acepto los <a href="/terms" target="_blank">términos y condiciones</a>
                                </label>
                                <p class="help is-danger terms-error" style="display: none;">Debe aceptar los términos y condiciones para continuar.</p>
                            </div>
                        </div>
                        
                        <div class="field">
                            <div class="control">
                                <label class="checkbox">
                                    <input type="checkbox" id="marketing_consent" name="marketing_consent" 
                                          <?php echo isset($formData['marketing_consent']) ? 'checked' : ''; ?>>
                                    Deseo recibir ofertas y novedades por correo electrónico
                                </label>
                            </div>
                        </div>
                        
                        <div class="field">
                            <div class="control">
                                <button type="submit" class="button is-primary is-fullwidth">
                                    <span class="icon">
                                        <i class="fas fa-user-plus"></i>
                                    </span>
                                    <span>Registrarse</span>
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="has-text-centered">
                        <p>¿Ya tiene cuenta? <a href="/login" class="has-text-primary">Iniciar Sesión</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Validación del formulario
    document.addEventListener('DOMContentLoaded', function() {
        // Para cerrar notificaciones
        document.querySelectorAll('.notification .delete').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.parentNode.remove();
            });
        });
        
        // Validación de formulario
        const form = document.getElementById('register-form');
        
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Validar nombre
            const nombresInput = document.getElementById('nombres');
            const nombresError = document.querySelector('.nombres-error');
            
            if (!nombresInput.value.trim()) {
                nombresInput.classList.add('is-danger');
                nombresError.style.display = 'block';
                isValid = false;
            } else {
                nombresInput.classList.remove('is-danger');
                nombresError.style.display = 'none';
            }
            
            // Validar apellidos
            const apellidosInput = document.getElementById('apellidos');
            const apellidosError = document.querySelector('.apellidos-error');
            
            if (!apellidosInput.value.trim()) {
                apellidosInput.classList.add('is-danger');
                apellidosError.style.display = 'block';
                isValid = false;
            } else {
                apellidosInput.classList.remove('is-danger');
                apellidosError.style.display = 'none';
            }
            
            // Validar email
            const emailInput = document.getElementById('email');
            const emailError = document.querySelector('.email-error');
            
            if (!emailInput.value.trim() || !validateEmail(emailInput.value)) {
                emailInput.classList.add('is-danger');
                emailError.style.display = 'block';
                isValid = false;
            } else {
                emailInput.classList.remove('is-danger');
                emailError.style.display = 'none';
            }
            
            // Validar contraseña
            const passwordInput = document.getElementById('password');
            const passwordError = document.querySelector('.password-error');
            
            if (!passwordInput.value.trim() || passwordInput.value.length < 8) {
                passwordInput.classList.add('is-danger');
                passwordError.style.display = 'block';
                isValid = false;
            } else {
                passwordInput.classList.remove('is-danger');
                passwordError.style.display = 'none';
            }
            
            // Validar confirmación de contraseña
            const confirmPasswordInput = document.getElementById('confirm_password');
            const confirmPasswordError = document.querySelector('.confirm-password-error');
            
            if (passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.classList.add('is-danger');
                confirmPasswordError.style.display = 'block';
                isValid = false;
            } else {
                confirmPasswordInput.classList.remove('is-danger');
                confirmPasswordError.style.display = 'none';
            }
            
            // Validar términos y condiciones
            const termsInput = document.getElementById('terms');
            const termsError = document.querySelector('.terms-error');
            
            if (!termsInput.checked) {
                termsError.style.display = 'block';
                isValid = false;
            } else {
                termsError.style.display = 'none';
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
        
        // Función para validar email
        function validateEmail(email) {
            const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }
    });
</script>

<?php
// Incluir footer
include_once __DIR__ . '/../../views/components/footer.php';
?>