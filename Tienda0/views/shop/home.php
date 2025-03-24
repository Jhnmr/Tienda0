<?php
// Definir título de la página
$pageTitle = 'Inicio';

// Incluir header
include_once __DIR__ . '/../../views/components/header.php';
?>

<!-- Hero section -->
<section class="hero is-primary is-medium">
    <div class="hero-body">
        <div class="container has-text-centered">
            <h1 class="title is-1">
                Bienvenido a Tienda0
            </h1>
            <h2 class="subtitle is-3">
                Tu tienda online de confianza
            </h2>
            <div class="buttons is-centered mt-5">
                <a href="/productos" class="button is-light is-large">
                    <span class="icon">
                        <i class="fas fa-shopping-bag"></i>
                    </span>
                    <span>Ver Productos</span>
                </a>
                <a href="/ofertas" class="button is-danger is-large">
                    <span class="icon">
                        <i class="fas fa-tag"></i>
                    </span>
                    <span>Ofertas</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Categorías destacadas -->
<section class="section">
    <div class="container">
        <h2 class="title has-text-centered">Categorías Destacadas</h2>
        <div class="columns is-multiline mt-5">
            <!-- Categoría 1 -->
            <div class="column is-3">
                <div class="card">
                    <div class="card-image">
                        <figure class="image is-4by3">
                            <img src="https://bulma.io/images/placeholders/1280x960.png" alt="Electrónica">
                        </figure>
                    </div>
                    <div class="card-content has-text-centered">
                        <p class="title is-4">Electrónica</p>
                        <a href="/categorias/1" class="button is-primary is-outlined is-fullwidth">Ver Productos</a>
                    </div>
                </div>
            </div>
            
            <!-- Categoría 2 -->
            <div class="column is-3">
                <div class="card">
                    <div class="card-image">
                        <figure class="image is-4by3">
                            <img src="https://bulma.io/images/placeholders/1280x960.png" alt="Ropa">
                        </figure>
                    </div>
                    <div class="card-content has-text-centered">
                        <p class="title is-4">Ropa</p>
                        <a href="/categorias/2" class="button is-primary is-outlined is-fullwidth">Ver Productos</a>
                    </div>
                </div>
            </div>
            
            <!-- Categoría 3 -->
            <div class="column is-3">
                <div class="card">
                    <div class="card-image">
                        <figure class="image is-4by3">
                            <img src="https://bulma.io/images/placeholders/1280x960.png" alt="Hogar">
                        </figure>
                    </div>
                    <div class="card-content has-text-centered">
                        <p class="title is-4">Hogar</p>
                        <a href="/categorias/3" class="button is-primary is-outlined is-fullwidth">Ver Productos</a>
                    </div>
                </div>
            </div>
            
            <!-- Categoría 4 -->
            <div class="column is-3">
                <div class="card">
                    <div class="card-image">
                        <figure class="image is-4by3">
                            <img src="https://bulma.io/images/placeholders/1280x960.png" alt="Deportes">
                        </figure>
                    </div>
                    <div class="card-content has-text-centered">
                        <p class="title is-4">Deportes</p>
                        <a href="/categorias/4" class="button is-primary is-outlined is-fullwidth">Ver Productos</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Productos destacados -->
<section class="section has-background-light">
    <div class="container">
        <h2 class="title has-text-centered">Productos Destacados</h2>
        <div class="columns is-multiline mt-5">
            <!-- Producto 1 -->
            <div class="column is-3">
                <div class="card product-card">
                    <div class="card-image">
                        <figure class="image is-4by3">
                            <img src="https://bulma.io/images/placeholders/1280x960.png" alt="Producto 1">
                            <span class="badge badge-new" style="position: absolute; top: 10px; right: 10px;">Nuevo</span>
                        </figure>
                    </div>
                    <div class="card-content">
                        <p class="title is-5">Smartphone XYZ</p>
                        <p class="subtitle is-6">Electrónica</p>
                        <div class="content">
                            <p>Smartphone de última generación con pantalla AMOLED y 8GB de RAM.</p>
                        </div>
                        <div class="price-container">
                            <span class="price">$599.99</span>
                            <span class="original-price">$699.99</span>
                            <span class="discount">-14%</span>
                        </div>
                    </div>
                    <footer class="card-footer">
                        <a href="/productos/1" class="card-footer-item">Ver Detalles</a>
                        <a href="#" class="card-footer-item add-to-cart" data-product-id="1">Agregar al Carrito</a>
                    </footer>
                </div>
            </div>
            
            <!-- Producto 2 -->
            <div class="column is-3">
                <div class="card product-card">
                    <div class="card-image">
                        <figure class="image is-4by3">
                            <img src="https://bulma.io/images/placeholders/1280x960.png" alt="Producto 2">
                            <span class="badge badge-sale" style="position: absolute; top: 10px; right: 10px;">Oferta</span>
                        </figure>
                    </div>
                    <div class="card-content">
                        <p class="title is-5">Laptop Ultrabook</p>
                        <p class="subtitle is-6">Electrónica</p>
                        <div class="content">
                            <p>Laptop ultradelgada con procesador Intel i7 y SSD de 512GB.</p>
                        </div>
                        <div class="price-container">
                            <span class="price">$1,199.99</span>
                            <span class="original-price">$1,499.99</span>
                            <span class="discount">-20%</span>
                        </div>
                    </div>
                    <footer class="card-footer">
                        <a href="/productos/2" class="card-footer-item">Ver Detalles</a>
                        <a href="#" class="card-footer-item add-to-cart" data-product-id="2">Agregar al Carrito</a>
                    </footer>
                </div>
            </div>
            
            <!-- Producto 3 -->
            <div class="column is-3">
                <div class="card product-card">
                    <div class="card-image">
                        <figure class="image is-4by3">
                            <img src="https://bulma.io/images/placeholders/1280x960.png" alt="Producto 3">
                        </figure>
                    </div>
                    <div class="card-content">
                        <p class="title is-5">Zapatillas Deportivas</p>
                        <p class="subtitle is-6">Deportes</p>
                        <div class="content">
                            <p>Zapatillas de running con tecnología de amortiguación avanzada.</p>
                        </div>
                        <div class="price-container">
                            <span class="price">$89.99</span>
                        </div>
                    </div>
                    <footer class="card-footer">
                        <a href="/productos/3" class="card-footer-item">Ver Detalles</a>
                        <a href="#" class="card-footer-item add-to-cart" data-product-id="3">Agregar al Carrito</a>
                    </footer>
                </div>
            </div>
            
            <!-- Producto 4 -->
            <div class="column is-3">
                <div class="card product-card">
                    <div class="card-image">
                        <figure class="image is-4by3">
                            <img src="https://bulma.io/images/placeholders/1280x960.png" alt="Producto 4">
                            <span class="badge badge-out-of-stock" style="position: absolute; top: 10px; right: 10px;">Agotado</span>
                        </figure>
                    </div>
                    <div class="card-content">
                        <p class="title is-5">Sofá Modular</p>
                        <p class="subtitle is-6">Hogar</p>
                        <div class="content">
                            <p>Sofá modular de 3 plazas con tela antimanchas y estructura de madera.</p>
                        </div>
                        <div class="price-container">
                            <span class="price">$799.99</span>
                        </div>
                    </div>
                    <footer class="card-footer">
                        <a href="/productos/4" class="card-footer-item">Ver Detalles</a>
                        <a href="#" class="card-footer-item" disabled>Agotado</a>
                    </footer>
                </div>
            </div>
        </div>
        
        <div class="has-text-centered mt-6">
            <a href="/productos" class="button is-primary is-large">Ver todos los productos</a>
        </div>
    </div>
</section>

<!-- Sección de características -->
<section class="section">
    <div class="container">
        <div class="columns is-multiline has-text-centered">
            <div class="column is-3">
                <span class="icon is-large">
                    <i class="fas fa-truck fa-3x"></i>
                </span>
                <h3 class="title is-4 mt-4">Envío Gratis</h3>
                <p>En compras superiores a $50</p>
            </div>
            
            <div class="column is-3">
                <span class="icon is-large">
                    <i class="fas fa-undo fa-3x"></i>
                </span>
                <h3 class="title is-4 mt-4">Devoluciones</h3>
                <p>30 días para devoluciones</p>
            </div>
            
            <div class="column is-3">
                <span class="icon is-large">
                    <i class="fas fa-lock fa-3x"></i>
                </span>
                <h3 class="title is-4 mt-4">Pago Seguro</h3>
                <p>Transacciones 100% seguras</p>
            </div>
            
            <div class="column is-3">
                <span class="icon is-large">
                    <i class="fas fa-headset fa-3x"></i>
                </span>
                <h3 class="title is-4 mt-4">Soporte 24/7</h3>
                <p>Atención al cliente constante</p>
            </div>
        </div>
    </div>
</section>

<!-- Sección de newsletter -->
<section class="section has-background-primary">
    <div class="container">
        <div class="columns is-vcentered">
            <div class="column is-6">
                <h2 class="title has-text-white">Suscríbete a nuestro newsletter</h2>
                <p class="subtitle has-text-white">Recibe las últimas novedades y ofertas exclusivas directamente en tu correo.</p>
            </div>
            <div class="column is-6">
                <form action="/newsletter/subscribe" method="POST">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <input class="input is-large" type="email" placeholder="Tu correo electrónico" required>
                        </div>
                        <div class="control">
                            <button type="submit" class="button is-info is-large">Suscribirse</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php
// Incluir footer
include_once __DIR__ . '/../../views/components/footer.php';
?>