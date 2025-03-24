<?php
// Función para verificar si una ruta está activa
function isAdminActive($path) {
    $currentUrl = $_SERVER['REQUEST_URI'];
    return (strpos($currentUrl, $path) !== false);
}
?>

<aside class="menu">
    <p class="menu-label">
        General
    </p>
    <ul class="menu-list">
        <li><a href="/admin/dashboard" class="<?php echo isAdminActive('/admin/dashboard') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-tachometer-alt"></i></span> 
            <span>Dashboard</span>
        </a></li>
    </ul>
    
    <p class="menu-label">
        Productos
    </p>
    <ul class="menu-list">
        <li><a href="/admin/products" class="<?php echo isAdminActive('/admin/products') && !isAdminActive('/admin/products/attributes') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-box"></i></span> 
            <span>Productos</span>
        </a></li>
        <li><a href="/admin/products/attributes" class="<?php echo isAdminActive('/admin/products/attributes') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-tags"></i></span> 
            <span>Atributos</span>
        </a></li>
        <li><a href="/admin/categories" class="<?php echo isAdminActive('/admin/categories') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-folder"></i></span> 
            <span>Categorías</span>
        </a></li>
    </ul>
    
    <p class="menu-label">
        Inventario
    </p>
    <ul class="menu-list">
        <li><a href="/admin/inventory" class="<?php echo isAdminActive('/admin/inventory') && !isAdminActive('/admin/inventory/movements') && !isAdminActive('/admin/inventory/stock') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-warehouse"></i></span> 
            <span>Inventario</span>
        </a></li>
        <li><a href="/admin/inventory/movements" class="<?php echo isAdminActive('/admin/inventory/movements') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-exchange-alt"></i></span> 
            <span>Movimientos</span>
        </a></li>
        <li><a href="/admin/inventory/stock" class="<?php echo isAdminActive('/admin/inventory/stock') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-cubes"></i></span> 
            <span>Stock</span>
        </a></li>
    </ul>
    
    <p class="menu-label">
        Ventas
    </p>
    <ul class="menu-list">
        <li><a href="/admin/orders" class="<?php echo isAdminActive('/admin/orders') && !isAdminActive('/admin/orders/invoices') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-shopping-cart"></i></span> 
            <span>Pedidos</span>
        </a></li>
        <li><a href="/admin/orders/invoices" class="<?php echo isAdminActive('/admin/orders/invoices') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-file-invoice-dollar"></i></span> 
            <span>Facturas</span>
        </a></li>
        <li><a href="/admin/promotions" class="<?php echo isAdminActive('/admin/promotions') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-percentage"></i></span> 
            <span>Promociones</span>
        </a></li>
    </ul>
    
    <p class="menu-label">
        Usuarios
    </p>
    <ul class="menu-list">
        <li><a href="/admin/users" class="<?php echo isAdminActive('/admin/users') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-users"></i></span> 
            <span>Usuarios</span>
        </a></li>
        <li><a href="/admin/roles" class="<?php echo isAdminActive('/admin/roles') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-user-tag"></i></span> 
            <span>Roles</span>
        </a></li>
    </ul>
    
    <p class="menu-label">
        Reportes
    </p>
    <ul class="menu-list">
        <li><a href="/admin/reports/sales" class="<?php echo isAdminActive('/admin/reports/sales') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-chart-line"></i></span> 
            <span>Ventas</span>
        </a></li>
        <li><a href="/admin/reports/products" class="<?php echo isAdminActive('/admin/reports/products') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-chart-bar"></i></span> 
            <span>Productos</span>
        </a></li>
        <li><a href="/admin/reports/customers" class="<?php echo isAdminActive('/admin/reports/customers') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-user-chart"></i></span> 
            <span>Clientes</span>
        </a></li>
        <li><a href="/admin/reports/inventory" class="<?php echo isAdminActive('/admin/reports/inventory') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-boxes"></i></span> 
            <span>Inventario</span>
        </a></li>
    </ul>
    
    <p class="menu-label">
        Configuración
    </p>
    <ul class="menu-list">
        <li><a href="/admin/settings/general" class="<?php echo isAdminActive('/admin/settings/general') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-cog"></i></span> 
            <span>General</span>
        </a></li>
        <li><a href="/admin/settings/payment" class="<?php echo isAdminActive('/admin/settings/payment') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-credit-card"></i></span> 
            <span>Pagos</span>
        </a></li>
        <li><a href="/admin/settings/shipping" class="<?php echo isAdminActive('/admin/settings/shipping') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-truck"></i></span> 
            <span>Envíos</span>
        </a></li>
        <li><a href="/admin/settings/emails" class="<?php echo isAdminActive('/admin/settings/emails') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-envelope"></i></span> 
            <span>Emails</span>
        </a></li>
        <li><a href="/admin/settings/taxes" class="<?php echo isAdminActive('/admin/settings/taxes') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-dollar-sign"></i></span> 
            <span>Impuestos</span>
        </a></li>
        <li><a href="/admin/settings/integrations" class="<?php echo isAdminActive('/admin/settings/integrations') ? 'is-active' : ''; ?>">
            <span class="icon"><i class="fas fa-plug"></i></span> 
            <span>Integraciones</span>
        </a></li>
    </ul>
</aside>