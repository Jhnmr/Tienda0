<?php
// Definir título de la página
$pageTitle = 'Acerca de Nosotros';

// Incluir header
include_once __DIR__ . '/../../views/components/header.php';
?>

<!-- Hero section -->
<section class="hero is-primary">
    <div class="hero-body">
        <div class="container">
            <h1 class="title is-1">Acerca de Nosotros</h1>
            <h2 class="subtitle is-3">Conozca nuestra historia y valores</h2>
        </div>
    </div>
</section>

<!-- Historia de la empresa -->
<section class="section">
    <div class="container">
        <div class="columns is-vcentered">
            <div class="column is-6">
                <h2 class="title is-2">Nuestra Historia</h2>
                <div class="content is-medium">
                    <p>
                        Fundada en 2020, <strong>Tienda0</strong> nació con la visión de crear una experiencia de compra online excepcional que combine tecnología avanzada, atención personalizada y productos de calidad.
                    </p>
                    <p>
                        Lo que comenzó como un pequeño emprendimiento, rápidamente se convirtió en una de las tiendas online de referencia en el mercado, gracias a nuestro compromiso con la excelencia y la satisfacción del cliente.
                    </p>
                    <p>
                        Hoy en día, <strong>Tienda0</strong> se enorgullece de ofrecer miles de productos cuidadosamente seleccionados, entrega rápida y un servicio de atención al cliente excepcional.
                    </p>
                </div>
            </div>
            <div class="column is-6">
                <figure class="image is-5by3">
                    <img src="https://bulma.io/images/placeholders/800x480.png" alt="Historia de Tienda0">
                </figure>
            </div>
        </div>
    </div>
</section>

<!-- Misión, Visión y Valores -->
<section class="section has-background-light">
    <div class="container">
        <h2 class="title is-2 has-text-centered mb-6">Misión, Visión y Valores</h2>
        
        <div class="columns">
            <div class="column is-4">
                <div class="box has-text-centered" style="height: 100%;">
                    <span class="icon is-large has-text-primary">
                        <i class="fas fa-bullseye fa-3x"></i>
                    </span>
                    <h3 class="title is-4 mt-4">Misión</h3>
                    <div class="content">
                        <p>
                            Proporcionar a nuestros clientes una experiencia de compra online excepcional, ofreciendo productos de calidad a precios competitivos y un servicio personalizado que supere sus expectativas.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="column is-4">
                <div class="box has-text-centered" style="height: 100%;">
                    <span class="icon is-large has-text-primary">
                        <i class="fas fa-eye fa-3x"></i>
                    </span>
                    <h3 class="title is-4 mt-4">Visión</h3>
                    <div class="content">
                        <p>
                            Convertirnos en el referente del comercio electrónico, reconocidos por nuestra innovación, excelencia operativa y compromiso con la satisfacción del cliente.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="column is-4">
                <div class="box has-text-centered" style="height: 100%;">
                    <span class="icon is-large has-text-primary">
                        <i class="fas fa-heart fa-3x"></i>
                    </span>
                    <h3 class="title is-4 mt-4">Valores</h3>
                    <div class="content">
                        <ul style="text-align: left;">
                            <li><strong>Integridad:</strong> Actuamos con honestidad y transparencia en todo momento.</li>
                            <li><strong>Excelencia:</strong> Buscamos la mejora continua en todos nuestros procesos.</li>
                            <li><strong>Compromiso:</strong> Nos dedicamos a la satisfacción total de nuestros clientes.</li>
                            <li><strong>Innovación:</strong> Adaptamos constantemente nuestras soluciones a las nuevas tecnologías.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Equipo -->
<section class="section">
    <div class="container">
        <h2 class="title is-2 has-text-centered mb-6">Nuestro Equipo</h2>
        
        <div class="columns is-multiline">
            <!-- Miembro 1 -->
            <div class="column is-3">
                <div class="card">
                    <div class="card-image">
                        <figure class="image is-square">
                            <img src="https://bulma.io/images/placeholders/480x480.png" alt="CEO">
                        </figure>
                    </div>
                    <div class="card-content has-text-centered">
                        <p class="title is-4">Carlos Rodríguez</p>
                        <p class="subtitle is-6">CEO & Fundador</p>
                        <div class="content">
                            <p>Emprendedor con más de 15 años de experiencia en comercio electrónico.</p>
                            <div class="social-links">
                                <a href="#" class="icon"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="icon"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Miembro 2 -->
            <div class="column is-3">
                <div class="card">
                    <div class="card-image">
                        <figure class="image is-square">
                            <img src="https://bulma.io/images/placeholders/480x480.png" alt="CTO">
                        </figure>
                    </div>
                    <div class="card-content has-text-centered">
                        <p class="title is-4">Ana Martínez</p>
                        <p class="subtitle is-6">Directora de Tecnología</p>
                        <div class="content">
                            <p>Ingeniera informática especializada en desarrollo web y soluciones e-commerce.</p>
                            <div class="social-links">
                                <a href="#" class="icon"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="icon"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Miembro 3 -->
            <div class="column is-3">
                <div class="card">
                    <div class="card-image">
                        <figure class="image is-square">
                            <img src="https://bulma.io/images/placeholders/480x480.png" alt="CMO">
                        </figure>
                    </div>
                    <div class="card-content has-text-centered">
                        <p class="title is-4">Roberto Sánchez</p>
                        <p class="subtitle is-6">Director de Marketing</p>
                        <div class="content">
                            <p>Experto en marketing digital con enfoque en estrategias de crecimiento.</p>
                            <div class="social-links">
                                <a href="#" class="icon"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="icon"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Miembro 4 -->
            <div class="column is-3">
                <div class="card">
                    <div class="card-image">
                        <figure class="image is-square">
                            <img src="https://bulma.io/images/placeholders/480x480.png" alt="COO">
                        </figure>
                    </div>
                    <div class="card-content has-text-centered">
                        <p class="title is-4">Laura González</p>
                        <p class="subtitle is-6">Directora de Operaciones</p>
                        <div class="content">
                            <p>Especialista en logística y gestión de cadena de suministro.</p>
                            <div class="social-links">
                                <a href="#" class="icon"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="icon"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonios -->
<section class="section has-background-light">
    <div class="container">
        <h2 class="title is-2 has-text-centered mb-6">Lo que dicen nuestros clientes</h2>
        
        <div class="columns">
            <!-- Testimonio 1 -->
            <div class="column is-4">
                <div class="box" style="height: 100%;">
                    <article class="media">
                        <div class="media-left">
                            <figure class="image is-64x64">
                                <img class="is-rounded" src="https://bulma.io/images/placeholders/128x128.png" alt="Cliente 1">
                            </figure>
                        </div>
                        <div class="media-content">
                            <div class="content">
                                <p>
                                    <strong>Marina López</strong>
                                    <br>
                                    "Excelente servicio y productos de calidad. Mi pedido llegó antes de lo esperado y el seguimiento fue perfecto. ¡Totalmente recomendado!"
                                </p>
                                <div class="stars has-text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
            
            <!-- Testimonio 2 -->
            <div class="column is-4">
                <div class="box" style="height: 100%;">
                    <article class="media">
                        <div class="media-left">
                            <figure class="image is-64x64">
                                <img class="is-rounded" src="https://bulma.io/images/placeholders/128x128.png" alt="Cliente 2">
                            </figure>
                        </div>
                        <div class="media-content">
                            <div class="content">
                                <p>
                                    <strong>Pedro Ramírez</strong>
                                    <br>
                                    "El proceso de compra fue muy sencillo y la atención al cliente excepcional. Tuve un problema con mi pedido y lo resolvieron inmediatamente."
                                </p>
                                <div class="stars has-text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
            
            <!-- Testimonio 3 -->
            <div class="column is-4">
                <div class="box" style="height: 100%;">
                    <article class="media">
                        <div class="media-left">
                            <figure class="image is-64x64">
                                <img class="is-rounded" src="https://bulma.io/images/placeholders/128x128.png" alt="Cliente 3">
                            </figure>
                        </div>
                        <div class="media-content">
                            <div class="content">
                                <p>
                                    <strong>Sofía Torres</strong>
                                    <br>
                                    "Precios competitivos y envío rápido. Ya he realizado varias compras y siempre quedo satisfecha. Sin duda, mi tienda online favorita."
                                </p>
                                <div class="stars has-text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contacto -->
<section class="section">
    <div class="container">
        <h2 class="title is-2 has-text-centered mb-6">Contáctanos</h2>
        
        <div class="columns">
            <div class="column is-6">
                <div class="content is-medium">
                    <p>
                        Estamos aquí para ayudarte. Si tienes alguna pregunta, sugerencia o problema, no dudes en ponerte en contacto con nosotros.
                    </p>
                    <ul>
                        <li><strong>Email:</strong> info@tienda0.com</li>
                        <li><strong>Teléfono:</strong> (01) 234-5678</li>
                        <li><strong>Horario de atención:</strong> Lunes a Viernes, 9am - 6pm</li>
                    </ul>
                    <p>
                        También puedes visitarnos en nuestras oficinas:
                    </p>
                    <p>
                        Av. Principal 123<br>
                        Ciudad Tecnológica<br>
                        Código Postal 12345
                    </p>
                </div>
            </div>
            <div class="column is-6">
                <form action="/contacto/enviar" method="POST">
                    <div class="field">
                        <label class="label">Nombre</label>
                        <div class="control">
                            <input class="input" type="text" name="nombre" placeholder="Tu nombre" required>
                        </div>
                    </div>
                    
                    <div class="field">
                        <label class="label">Email</label>
                        <div class="control">
                            <input class="input" type="email" name="email" placeholder="Tu correo electrónico" required>
                        </div>
                    </div>
                    
                    <div class="field">
                        <label class="label">Asunto</label>
                        <div class="control">
                            <input class="input" type="text" name="asunto" placeholder="Asunto del mensaje" required>
                        </div>
                    </div>
                    
                    <div class="field">
                        <label class="label">Mensaje</label>
                        <div class="control">
                            <textarea class="textarea" name="mensaje" placeholder="Tu mensaje" rows="5" required></textarea>
                        </div>
                    </div>
                    
                    <div class="field">
                        <div class="control">
                            <button type="submit" class="button is-primary is-fullwidth">Enviar Mensaje</button>
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