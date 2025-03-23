<?php
// Determine current page
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>

<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Panel de Administración</h5>
    </div>
    <div class="list-group list-group-flush">
        <a href="/admin/dashboard.php" class="list-group-item list-group-item-action <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        
        <?php if (userHasPermission('user_view')): ?>
        <a href="/admin/users.php" class="list-group-item list-group-item-action <?php echo $currentPage == 'users.php' ? 'active' : ''; ?>">
            <i class="bi bi-people me-2"></i> Usuarios
        </a>
        <?php endif; ?>
        
        <?php if (userHasPermission('role_view')): ?>
        <a href="/admin/roles.php" class="list-group-item list-group-item-action <?php echo $currentPage == 'roles.php' ? 'active' : ''; ?>">
            <i class="bi bi-shield-lock me-2"></i> Roles
        </a>
        <?php endif; ?>
        
        <?php if (userHasPermission('product_view')): ?>
        <a href="/admin/products.php" class="list-group-item list-group-item-action <?php echo $currentPage == 'products.php' ? 'active' : ''; ?>">
            <i class="bi bi-box me-2"></i> Productos
        </a>
        <?php endif; ?>
        
        <?php if (userHasPermission('category_view')): ?>
        <a href="/admin/categories.php" class="list-group-item list-group-item-action <?php echo $currentPage == 'categories.php' ? 'active' : ''; ?>">
            <i class="bi bi-tags me-2"></i> Categorías
        </a>
        <?php endif; ?>
        
        <?php if (userHasPermission('order_view')): ?>
        <a href="/admin/orders.php" class="list-group-item list-group-item-action <?php echo $currentPage == 'orders.php' ? 'active' : ''; ?>">
            <i class="bi bi-cart3 me-2"></i> Pedidos
        </a>
        <?php endif; ?>
        
        <div class="dropdown-divider"></div>
        
        <a href="/admin/profile.php" class="list-group-item list-group-item-action <?php echo $currentPage == 'profile.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-circle me-2"></i> Mi Perfil
        </a>
        
        <a href="/logout.php" class="list-group-item list-group-item-action text-danger">
            <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
        </a>
    </div>
</div>