</main>
    
    <!-- Pie de página -->
    <footer class="footer">
        <div class="container">
            <div class="columns">
                <div class="column is-4">
                    <h3 class="title is-4">TIENDA0</h3>
                    <p class="pb-4">Tu tienda online de confianza para encontrar los mejores productos al mejor precio.</p>
                    <div class="social-icons">
                        <a href="#" class="icon is-medium">
                            <i class="fab fa-facebook fa-lg"></i>
                        </a>
                        <a href="#" class="icon is-medium">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                        <a href="#" class="icon is-medium">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="#" class="icon is-medium">
                            <i class="fab fa-linkedin fa-lg"></i>
                        </a>
                    </div>
                </div>
                
                <div class="column is-2">
                    <h4 class="title is-5">Enlaces Rápidos</h4>
                    <ul>
                        <li><a href="/">Inicio</a></li>
                        <li><a href="/productos">Productos</a></li>
                        <li><a href="/categorias">Categorías</a></li>
                        <li><a href="/ofertas">Ofertas</a></li>
                        <li><a href="/about">Acerca de</a></li>
                        <li><a href="/contacto">Contacto</a></li>
                    </ul>
                </div>
                
                <div class="column is-3">
                    <h4 class="title is-5">Categorías Populares</h4>
                    <ul>
                        <li><a href="/categorias/1">Electrónica</a></li>
                        <li><a href="/categorias/2">Ropa</a></li>
                        <li><a href="/categorias/3">Hogar</a></li>
                        <li><a href="/categorias/4">Deportes</a></li>
                        <li><a href="/categorias/5">Juguetes</a></li>
                    </ul>
                </div>
                
                <div class="column is-3">
                    <h4 class="title is-5">Información</h4>
                    <ul>
                        <li><a href="/terminos">Términos y condiciones</a></li>
                        <li><a href="/privacidad">Política de privacidad</a></li>
                        <li><a href="/envios">Política de envíos</a></li>
                        <li><a href="/devoluciones">Política de devoluciones</a></li>
                        <li><a href="/preguntas-frecuentes">Preguntas frecuentes</a></li>
                    </ul>
                </div>
            </div>
            
            <hr>
            
            <div class="columns">
                <div class="column">
                    <p class="has-text-centered">
                        &copy; <?php echo date('Y'); ?> TIENDA0. Todos los derechos reservados.
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script>
        // Activar hamburger menu en móviles
        document.addEventListener('DOMContentLoaded', () => {
            const navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
            
            if (navbarBurgers.length > 0) {
                navbarBurgers.forEach(el => {
                    el.addEventListener('click', () => {
                        const target = document.getElementById(el.dataset.target);
                        el.classList.toggle('is-active');
                        target.classList.toggle('is-active');
                    });
                });
            }
        });
    </script>
    
    <!-- Script personalizado -->
    <script src="/public/assets/js/main.js"></script>
</body>
</html>