<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivo de helpers
require_once __DIR__ . '/../../utils/helpers.php';

// Verificar si la función e() ya existe antes de declararla
if (!function_exists('e')) {
    // Función para escapar output (prevenir XSS)
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

// Verificar si la función generateCSRFToken() ya existe antes de declararla
if (!function_exists('generateCSRFToken')) {
    // Función para generar token CSRF
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Verificar si la función isAuthenticated() ya existe antes de declararla
if (!function_exists('isAuthenticated')) {
    // Función para verificar si el usuario está autenticado
    function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
}

// Verificar si la función isActive() ya existe antes de declararla
if (!function_exists('isActive')) {
    // Función para verificar si la ruta actual coincide
    function isActive($path) {
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return $currentPath === $path;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda0 - <?php echo isset($pageTitle) ? e($pageTitle) : 'Tu tienda online'; ?></title>
    
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="/assets/css/main.css">
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/img/favicon.ico">
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar is-primary" role="navigation" aria-label="main navigation">
        <div class="container">
            <div class="navbar-brand">
                <a class="navbar-item" href="/">
                    <strong>TIENDA0</strong>
                </a>

                <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbarBasic">
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                </a>
            </div>

            <div id="navbarBasic" class="navbar-menu">
                <div class="navbar-start">
                    <a class="navbar-item <?php echo isActive('/') ? 'is-active' : ''; ?>" href="/">
                        Inicio
                    </a>

                    <a class="navbar-item <?php echo isActive('/productos') ? 'is-active' : ''; ?>" href="/productos">
                        Productos
                    </a>

                    <a class="navbar-item <?php echo isActive('/categorias') ? 'is-active' : ''; ?>" href="/categorias">
                        Categorías
                    </a>

                    <div class="navbar-item has-dropdown is-hoverable">
                        <a class="navbar-link">
                            Más
                        </a>

                        <div class="navbar-dropdown">
                            <a class="navbar-item <?php echo isActive('/about') ? 'is-active' : ''; ?>" href="/about">
                                Acerca de
                            </a>
                            <a class="navbar-item <?php echo isActive('/contacto') ? 'is-active' : ''; ?>" href="/contacto">
                                Contacto
                            </a>
                            <hr class="navbar-divider">
                            <a class="navbar-item">
                                Reportar un problema
                            </a>
                        </div>
                    </div>
                </div>

                <div class="navbar-end">
                    <div class="navbar-item">
                        <a class="navbar-item" href="/carrito">
                            <span class="icon">
                                <i class="fas fa-shopping-cart"></i>
                            </span>
                            <span class="cart-count">0</span>
                        </a>
                    </div>
                    
                    <div class="navbar-item">
                        <div class="buttons">
                            <?php if (isAuthenticated()): ?>
                                <div class="navbar-item has-dropdown is-hoverable">
                                    <a class="navbar-link">
                                        <span class="icon">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <span><?php echo isset($_SESSION['user_name']) ? e($_SESSION['user_name']) : 'Mi cuenta'; ?></span>
                                    </a>

                                    <div class="navbar-dropdown is-right">
                                        <a class="navbar-item" href="/cuenta">
                                            <span class="icon">
                                                <i class="fas fa-user-circle"></i>
                                            </span>
                                            <span>Mi Perfil</span>
                                        </a>
                                        <a class="navbar-item" href="/cuenta/pedidos">
                                            <span class="icon">
                                                <i class="fas fa-box"></i>
                                            </span>
                                            <span>Mis Pedidos</span>
                                        </a>
                                        <a class="navbar-item" href="/cuenta/wishlist">
                                            <span class="icon">
                                                <i class="fas fa-heart"></i>
                                            </span>
                                            <span>Lista de Deseos</span>
                                        </a>
                                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                            <hr class="navbar-divider">
                                            <a class="navbar-item" href="/admin">
                                                <span class="icon">
                                                    <i class="fas fa-cog"></i>
                                                </span>
                                                <span>Panel de Administración</span>
                                            </a>
                                        <?php endif; ?>
                                        <hr class="navbar-divider">
                                        <a class="navbar-item" href="/logout">
                                            <span class="icon">
                                                <i class="fas fa-sign-out-alt"></i>
                                            </span>
                                            <span>Cerrar Sesión</span>
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <a class="button is-primary" href="/register">
                                    <strong>Registrarse</strong>
                                </a>
                                <a class="button is-light" href="/login">
                                    Iniciar Sesión
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenedor principal -->
    <main class="section">