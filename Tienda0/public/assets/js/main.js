/**
 * Funcionalidades comunes para Tienda0
 */

document.addEventListener('DOMContentLoaded', function() {
    // Función para cerrar notificaciones
    setupNotifications();
    
    // Inicializar dropdowns
    setupDropdowns();
    
    // Inicializar carrito
    setupCart();
});

/**
 * Configura el comportamiento de las notificaciones
 */
function setupNotifications() {
    // Para cerrar notificaciones
    document.querySelectorAll('.notification .delete').forEach(deleteButton => {
        const notification = deleteButton.parentNode;
        
        deleteButton.addEventListener('click', () => {
            notification.remove();
        });
    });
    
    // Auto-cerrar notificaciones después de 5 segundos
    setTimeout(() => {
        document.querySelectorAll('.notification').forEach(notification => {
            notification.classList.add('is-hidden');
            setTimeout(() => {
                notification.remove();
            }, 500);
        });
    }, 5000);
}

/**
 * Configura los dropdowns del menú
 */
function setupDropdowns() {
    const dropdowns = document.querySelectorAll('.has-dropdown');
    
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function(event) {
            event.stopPropagation();
            dropdown.classList.toggle('is-active');
        });
    });
    
    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', () => {
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('is-active');
        });
    });
}

/**
 * Configura funcionalidades relacionadas con el carrito
 */
function setupCart() {
    // Botones de agregar al carrito
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            
            const productId = this.dataset.productId;
            const quantity = document.getElementById(`quantity-${productId}`)?.value || 1;
            
            addToCart(productId, quantity);
        });
    });
    
    // Actualizar contador de carrito (ejemplo)
    updateCartCount();
}

/**
 * Agrega un producto al carrito
 */
function addToCart(productId, quantity) {
    // Aquí iría la lógica para agregar al carrito
    // Podría ser una llamada AJAX a tu backend
    
    console.log(`Agregando producto ID: ${productId}, Cantidad: ${quantity}`);
    
    // Mostrar notificación
    showNotification('Producto agregado al carrito correctamente.', 'is-success');
    
    // Actualizar contador
    updateCartCount();
}

/**
 * Actualiza el contador de elementos en el carrito
 */
function updateCartCount() {
    // Aquí iría la lógica para obtener la cantidad real de elementos en el carrito
    // Por ahora, usamos un ejemplo estático
    const cartItems = 5;
    
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = cartItems;
    });
}

/**
 * Muestra una notificación
 */
function showNotification(message, type = 'is-info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type} is-light`;
    notification.style.position = 'fixed';
    notification.style.top = '1rem';
    notification.style.right = '1rem';
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '300px';
    
    const deleteButton = document.createElement('button');
    deleteButton.className = 'delete';
    deleteButton.addEventListener('click', () => {
        notification.remove();
    });
    
    notification.appendChild(deleteButton);
    notification.appendChild(document.createTextNode(message));
    
    document.body.appendChild(notification);
    
    // Auto-cerrar después de 3 segundos
    setTimeout(() => {
        notification.classList.add('is-hidden');
        setTimeout(() => {
            notification.remove();
        }, 500);
    }, 3000);
}