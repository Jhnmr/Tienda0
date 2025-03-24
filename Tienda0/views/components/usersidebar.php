<?php
// Función para verificar si una ruta está activa
function isUserActive($path) {
    $currentUrl = $_SERVER['REQUEST_URI'];
    return (strpos($currentUrl, $path) !== false);
}
?>

<div class="box">
    <aside class="menu">
        <p class="menu-label">
            Mi Cuenta
        </p>
        <ul class="menu-list">
            <li><a href="/account/dashboard" class="<?php echo isUserActive('/account/dashboard') ? 'is-active' : ''; ?>">
                <span class="icon"><i class="fas fa-tachometer-alt"></i></span> 
                <span>Panel Principal</span>
            </a></li>
            <li><a href="/account/profile" class="<?php echo isUserActive('/account/profile') ? 'is-active' : ''; ?>">
                <span class="icon"><i class="fas fa-user"></i></span> 
                <span>Mi Perfil</span>
            </a></li>
            <li><a href="/account/orders" class="<?php echo isUserActive('/account/orders') ? 'is-active' : ''; ?>">
                <span class="icon"><i class="fas fa-shopping-bag"></i></span> 
                <span>Mis Pedidos</span>
            </a></li>
            <li><a href="/account/addresses" class="<?php echo isUserActive('/account/addresses') ? 'is-active' : ''; ?>">
                <span class="icon"><i class="fas fa-map-marker-alt"></i></span> 
                <span>Mis Direcciones</span>
            </a></li>
            <li><a href="/account/wishlist" class="<?php echo isUserActive('/account/wishlist') ? 'is-active' : ''; ?>">
                <span class="icon"><i class="fas fa-heart"></i></span> 
                <span>Lista de Deseos</span>
            </a></li>
        </ul>
        
        <p class="menu-label">
            Configuración
        </p>
        <ul class="menu-list">
            <li><a href="/auth/changepassword" class="<?php echo isUserActive('/auth/changepassword') ? 'is-active' : ''; ?>">
                <span class="icon"><i class="fas fa-lock"></i></span> 
                <span>Cambiar Contraseña</span>
            </a></li>
            <li><a href="/account/notifications" class="<?php echo isUserActive('/account/notifications') ? 'is-active' : ''; ?>">
                <span class="icon"><i class="fas fa-bell"></i></span> 
                <span>Notificaciones</span>
            </a></li>
        </ul>
        
        <hr>
        
        <div class="buttons">
            <a href="/logout" class="button is-danger is-outlined is-fullwidth">
                <span class="icon">
                    <i class="fas fa-sign-out-alt"></i>
                </span>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </aside>
</div>