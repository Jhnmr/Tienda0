<?php
/**
 * Componente para estadísticas de roles en el dashboard
 */

// Obtener estadísticas de roles
$db = Database::getInstance()->getConnection();

// Cantidad de usuarios por rol
$roleStats = [];
$stmt = $db->query("
    SELECT r.id_rol, r.nombre, COUNT(ur.id_usuario) as total_usuarios
    FROM rol r
    LEFT JOIN usuario_rol ur ON r.id_rol = ur.id_rol
    GROUP BY r.id_rol, r.nombre
    ORDER BY total_usuarios DESC
");

while ($row = $stmt->fetch()) {
    $roleStats[] = $row;
}

// Total de permisos en el sistema
$stmtPermissions = $db->query("SELECT COUNT(*) FROM permiso");
$totalPermissions = $stmtPermissions->fetchColumn();

// Total de roles en el sistema
$stmtRoles = $db->query("SELECT COUNT(*) FROM rol");
$totalRoles = $stmtRoles->fetchColumn();

// Usuarios sin rol asignado
$stmtNoRole = $db->query("
    SELECT COUNT(*) FROM usuario u
    WHERE NOT EXISTS (
        SELECT 1 FROM usuario_rol ur WHERE ur.id_usuario = u.id_usuario
    )
");
$usersWithoutRole = $stmtNoRole->fetchColumn();
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Estadísticas de Roles</h6>
        <?php if (userHasPermission('role_view')): ?>
        <a href="/admin/roles.php" class="btn btn-sm btn-primary">
            Ver Todos
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="text-center mb-3">
                    <h4><?php echo $totalRoles; ?></h4>
                    <p class="text-muted">Roles definidos</p>
                </div>
                
                <div class="text-center">
                    <h4><?php echo $totalPermissions; ?></h4>
                    <p class="text-muted">Permisos en el sistema</p>
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="font-weight-bold">Usuarios por Rol</h6>
                
                <?php if (empty($roleStats)): ?>
                <p class="text-center text-muted">No hay datos disponibles</p>
                <?php else: ?>
                    <?php foreach ($roleStats as $stat): ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span><?php echo e($stat['nombre']); ?></span>
                            <span><?php echo e($stat['total_usuarios']); ?> usuarios</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <?php 
                            // Calcular porcentaje para la barra de progreso
                            $totalUsers = array_sum(array_column($roleStats, 'total_usuarios'));
                            $percent = $totalUsers > 0 ? ($stat['total_usuarios'] / $totalUsers) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $percent; ?>%" 
                                aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($usersWithoutRole > 0): ?>
                <div class="alert alert-warning mt-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill mr-2"></i>
                    Hay <?php echo $usersWithoutRole; ?> usuarios sin rol asignado.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>