<?php
/**
 * Componente de tarjeta de producto
 * 
 * Uso:
 * include_once __DIR__ . '/../components/productcard.php';
 * 
 * $product = [
 *     'id' => 1,
 *     'name' => 'Smartphone XYZ',
 *     'description' => 'Descripción breve del producto...',
 *     'price' => 599.99,
 *     'old_price' => 699.99, // Opcional
 *     'discount' => 14, // Opcional
 *     'image' => '/assets/img/products/smartphone.jpg',
 *     'category' => 'Electrónica',
 *     'is_new' => true, // Opcional
 *     'is_sale' => false, // Opcional
 *     'stock' => 10, // Opcional
 *     'rating' => 4.5, // Opcional
 *     'reviews_count' => 120 // Opcional
 * ];
 * 
 * showProductCard($product);
 */

/**
 * Muestra una tarjeta de producto
 * 
 * @param array $product Datos del producto
 */
function showProductCard($product) {
    // Validar que sea un producto válido
    if (empty($product['id']) || empty($product['name'])) {
        return;
    }
    
    // Iniciar la tarjeta
    echo '<div class="card product-card">';
    
    // Imagen del producto
    echo '<div class="card-image">';
    echo '<figure class="image is-4by3">';
    echo '<img src="' . e($product['image'] ?? 'https://bulma.io/images/placeholders/1280x960.png') . '" alt="' . e($product['name']) . '">';
    
    // Mostrar badges
    if (!empty($product['is_new']) && $product['is_new']) {
        echo '<span class="badge badge-new" style="position: absolute; top: 10px; right: 10px;">Nuevo</span>';
    } elseif (!empty($product['is_sale']) && $product['is_sale']) {
        echo '<span class="badge badge-sale" style="position: absolute; top: 10px; right: 10px;">Oferta</span>';
    } elseif (!empty($product['stock']) && $product['stock'] <= 0) {
        echo '<span class="badge badge-out-of-stock" style="position: absolute; top: 10px; right: 10px;">Agotado</span>';
    }
    
    echo '</figure>';
    echo '</div>';
    
    // Contenido de la tarjeta
    echo '<div class="card-content">';
    
    // Nombre y categoría
    echo '<p class="title is-5">' . e($product['name']) . '</p>';
    
    if (!empty($product['category'])) {
        echo '<p class="subtitle is-6">' . e($product['category']) . '</p>';
    }
    
    // Descripción
    if (!empty($product['description'])) {
        echo '<div class="content">';
        echo '<p>' . e($product['description']) . '</p>';
        echo '</div>';
    }
    
    // Precio y descuento
    echo '<div class="price-container">';
    echo '<span class="price">$' . number_format($product['price'], 2) . '</span>';
    
    if (!empty($product['old_price']) && $product['old_price'] > $product['price']) {
        echo '<span class="original-price">$' . number_format($product['old_price'], 2) . '</span>';
        
        if (!empty($product['discount'])) {
            echo '<span class="discount">-' . $product['discount'] . '%</span>';
        }
    }
    echo '</div>';
    
    // Valoración
    if (!empty($product['rating'])) {
        echo '<div class="rating mt-2">';
        
        // Estrellas
        echo '<div class="stars has-text-warning">';
        $fullStars = floor($product['rating']);
        $halfStar = ($product['rating'] - $fullStars) >= 0.5;
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $fullStars) {
                echo '<i class="fas fa-star"></i>';
            } elseif ($i == $fullStars + 1 && $halfStar) {
                echo '<i class="fas fa-star-half-alt"></i>';
            } else {
                echo '<i class="far fa-star"></i>';
            }
        }
        
        echo '</div>';
        
        // Número de reseñas
        if (!empty($product['reviews_count'])) {
            echo '<span class="reviews-count">(' . $product['reviews_count'] . ' reseñas)</span>';
        }
        
        echo '</div>';
    }
    
    echo '</div>';
    
    // Pie de la tarjeta
    echo '<footer class="card-footer">';
    echo '<a href="/productos/' . $product['id'] . '" class="card-footer-item">Ver Detalles</a>';
    
    if (empty($product['stock']) || $product['stock'] > 0) {
        echo '<a href="#" class="card-footer-item add-to-cart" data-product-id="' . $product['id'] . '">Agregar al Carrito</a>';
    } else {
        echo '<a href="#" class="card-footer-item" disabled>Agotado</a>';
    }
    
    echo '</footer>';
    
    // Cerrar la tarjeta
    echo '</div>';
}