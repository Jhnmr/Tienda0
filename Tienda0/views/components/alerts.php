<?php
/**
 * Componente para mostrar alertas
 * 
 * Uso:
 * include_once __DIR__ . '/../components/alerts.php';
 * 
 * Para mostrar alertas almacenadas en la sesión, usar:
 * showSessionAlerts();
 * 
 * Para mostrar una alerta directamente:
 * showAlert('mensaje', 'tipo');
 * Tipos: success, error, warning, info
 */

/**
 * Muestra alertas guardadas en sesión
 */
function showSessionAlerts() {
    // Alerta de error
    if (isset($_SESSION['error_message'])) {
        showAlert($_SESSION['error_message'], 'error');
        unset($_SESSION['error_message']);
    }
    
    // Alerta de éxito
    if (isset($_SESSION['success_message'])) {
        showAlert($_SESSION['success_message'], 'success');
        unset($_SESSION['success_message']);
    }
    
    // Alerta de advertencia
    if (isset($_SESSION['warning_message'])) {
        showAlert($_SESSION['warning_message'], 'warning');
        unset($_SESSION['warning_message']);
    }
    
    // Alerta informativa
    if (isset($_SESSION['info_message'])) {
        showAlert($_SESSION['info_message'], 'info');
        unset($_SESSION['info_message']);
    }
}

/**
 * Muestra una alerta
 * 
 * @param string $message Mensaje de la alerta
 * @param string $type Tipo de alerta (success, error, warning, info)
 */
function showAlert($message, $type = 'info') {
    // Mapeo de tipos a clases de Bulma
    $typeMap = [
        'success' => 'is-success',
        'error' => 'is-danger',
        'warning' => 'is-warning',
        'info' => 'is-info'
    ];
    
    // Mapeo de tipos a iconos de Font Awesome
    $iconMap = [
        'success' => 'fa-check-circle',
        'error' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle'
    ];
    
    // Obtener clase e icono
    $class = $typeMap[$type] ?? 'is-info';
    $icon = $iconMap[$type] ?? 'fa-info-circle';
    
    // Mostrar la alerta
    echo '<div class="notification ' . $class . ' is-light">';
    echo '<button class="delete"></button>';
    echo '<div class="media">';
    echo '<div class="media-left">';
    echo '<span class="icon is-medium"><i class="fas ' . $icon . ' fa-lg"></i></span>';
    echo '</div>';
    echo '<div class="media-content">';
    echo $message;
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
?>

<script>
    // Cerrar notificaciones
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.notification .delete').forEach(function(deleteButton) {
            deleteButton.addEventListener('click', function() {
                this.parentElement.remove();
            });
        });
    });
</script>