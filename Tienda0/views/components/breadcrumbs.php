<?php
/**
 * Componente de migas de pan (breadcrumbs)
 * 
 * Uso:
 * include_once __DIR__ . '/../components/breadcrumbs.php';
 * 
 * $breadcrumbs = [
 *     ['text' => 'Inicio', 'url' => '/'],
 *     ['text' => 'Categorías', 'url' => '/categorias'],
 *     ['text' => 'Electrónica', 'url' => ''] // El último elemento no tiene URL
 * ];
 * 
 * showBreadcrumbs($breadcrumbs);
 */

/**
 * Muestra las migas de pan
 * 
 * @param array $items Elementos de las migas de pan
 */
function showBreadcrumbs($items) {
    // Si no hay elementos, no mostrar nada
    if (empty($items)) {
        return;
    }
    
    echo '<nav class="breadcrumb" aria-label="breadcrumbs">';
    echo '<ul>';
    
    $count = count($items);
    foreach ($items as $index => $item) {
        $isLast = ($index === $count - 1);
        
        if ($isLast) {
            // Último elemento (activo)
            echo '<li class="is-active"><a href="#" aria-current="page">' . e($item['text']) . '</a></li>';
        } else {
            // Elementos con enlace
            echo '<li><a href="' . e($item['url']) . '">' . e($item['text']) . '</a></li>';
        }
    }
    
    echo '</ul>';
    echo '</nav>';
}