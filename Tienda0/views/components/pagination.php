<?php
/**
 * Componente de paginación
 * 
 * Uso:
 * include_once __DIR__ . '/../components/pagination.php';
 * 
 * $pagination = [
 *     'currentPage' => 1,
 *     'totalPages' => 10,
 *     'baseUrl' => '/productos',
 *     'queryParams' => ['categoria' => 1, 'orden' => 'precio'] // Opcional
 * ];
 * 
 * showPagination($pagination);
 */

/**
 * Muestra la paginación
 * 
 * @param array $config Configuración de la paginación
 */
function showPagination($config) {
    // Valores por defecto
    $defaults = [
        'currentPage' => 1,
        'totalPages' => 1,
        'baseUrl' => '/',
        'queryParams' => [],
        'showFirst' => true,
        'showLast' => true,
        'maxPages' => 5
    ];
    
    // Fusionar configuración con valores por defecto
    $config = array_merge($defaults, $config);
    
    // Si solo hay una página, no mostrar paginación
    if ($config['totalPages'] <= 1) {
        return;
    }
    
    // Extraer variables
    $currentPage = $config['currentPage'];
    $totalPages = $config['totalPages'];
    $baseUrl = $config['baseUrl'];
    $queryParams = $config['queryParams'];
    $showFirst = $config['showFirst'];
    $showLast = $config['showLast'];
    $maxPages = $config['maxPages'];
    
    // Preparar parámetros de consulta
    $queryString = '';
    if (!empty($queryParams)) {
        $params = $queryParams;
        
        // Asegurarse de que page no esté en los parámetros
        unset($params['page']);
        
        if (!empty($params)) {
            $queryString = '&' . http_build_query($params);
        }
    }
    
    // Calcular el rango de páginas a mostrar
    $startPage = max(1, $currentPage - floor($maxPages / 2));
    $endPage = min($totalPages, $startPage + $maxPages - 1);
    
    // Ajustar el inicio si estamos cerca del final
    if ($endPage - $startPage + 1 < $maxPages) {
        $startPage = max(1, $endPage - $maxPages + 1);
    }
    
    // Iniciar la paginación
    echo '<nav class="pagination is-centered" role="navigation" aria-label="pagination">';
    
    // Botón "Anterior"
    if ($currentPage > 1) {
        echo '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . $queryString . '" class="pagination-previous">Anterior</a>';
    } else {
        echo '<a class="pagination-previous" disabled>Anterior</a>';
    }
    
    // Botón "Siguiente"
    if ($currentPage < $totalPages) {
        echo '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . $queryString . '" class="pagination-next">Siguiente</a>';
    } else {
        echo '<a class="pagination-next" disabled>Siguiente</a>';
    }
    
    // Lista de páginas
    echo '<ul class="pagination-list">';
    
    // Primera página
    if ($showFirst && $startPage > 1) {
        echo '<li><a href="' . $baseUrl . '?page=1' . $queryString . '" class="pagination-link" aria-label="Ir a página 1">1</a></li>';
        
        if ($startPage > 2) {
            echo '<li><span class="pagination-ellipsis">&hellip;</span></li>';
        }
    }
    
    // Páginas intermedias
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            echo '<li><a class="pagination-link is-current" aria-label="Página ' . $i . '" aria-current="page">' . $i . '</a></li>';
        } else {
            echo '<li><a href="' . $baseUrl . '?page=' . $i . $queryString . '" class="pagination-link" aria-label="Ir a página ' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Última página
    if ($showLast && $endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            echo '<li><span class="pagination-ellipsis">&hellip;</span></li>';
        }
        
        echo '<li><a href="' . $baseUrl . '?page=' . $totalPages . $queryString . '" class="pagination-link" aria-label="Ir a página ' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    echo '</ul>';
    echo '</nav>';
}