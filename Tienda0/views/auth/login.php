<?php
// Definir título de la página
$pageTitle = 'Iniciar Sesión';

// Incluir header
include_once __DIR__ . '/../../views/components/header.php';
?>

<section class="section">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-half">
                <div class="box">
                    <h1 class="title has-text-centered has-text-primary">Iniciar Sesión</h1>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="notification is-danger is-light">
                            <button class="delete"></button>
                            <?php 
                                echo $_SESSION['error_message'];
                                unset($_SESSION['error_message']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="notification is-success is-light">
                            <button class="delete"></button>
                            <?php 
                                echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="/login" method="POST" id="login-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="field">
                            <label class="label" for="email">Correo Electrónico</label>
                            <div class="control has-icons-left">
                                <input class="input" type="email" id="email" name="email" placeholder="ejemplo@correo.com" required>
                                <span class="icon is-small is-left">
                                    <i class="fas fa-envelope"></i>
                                </span>
                            </div>
                            <p class="help is-danger email-error" style="display: none;">Por favor, ingrese un correo electrónico válido.</p>
                        </div>
                        
                        <div class="field">
                            <label class="label" for="password">Contraseña</label>
                            <div class="control has-icons-left">
                                <input class="input" type="password" id="password" name="password" placeholder="Su contraseña" required>
                                <span class="icon is-small is-left">
                                    <i class="fas fa-lock"></i>
                                </span>
                            </div>
                            <p class="help is-danger password-error" style="display: none;">Por favor, ingrese su contraseña.</p>
                        </div>
                        
                        <div class="field">
                            <div class="control">
                                <label class="checkbox">
                                    <input type="checkbox" name="remember">
                                    Recordarme
                                </label>
                            </div>
                        </div>
                        
                        <div class="field">
                            <div class="control">
                                <button type="submit" class="button is-primary is-fullwidth">
                                    <span class="icon">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </span>
                                    <span>Iniciar Sesión</span>
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="has-text-centered mt-4">
                        <a href="/forgot-password" class="has-text-link">¿Olvidó su contraseña?</a>
                    </div>
                    
                    <hr>
                    
                    <div class="has-text-centered">
                        <p>¿No tiene cuenta? <a href="/register" class="has-text-primary">Regístrese aquí</a></p>
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
        const form = document.getElementById('login-form');
        
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
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
            
            if (!passwordInput.value.trim()) {
                passwordInput.classList.add('is-danger');
                passwordError.style.display = 'block';
                isValid = false;
            } else {
                passwordInput.classList.remove('is-danger');
                passwordError.style.display = 'none';
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