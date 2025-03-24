<?php
class homecontroller {
    public function __construct() {
        // Incluir modelos si son necesarios
        // require_once __DIR__ . '/../../models/product.php';
    }
    
    public function index() {
        // Obtener productos destacados, ofertas, etc.
        $featuredProducts = []; // Aquí cargarías productos destacados desde el modelo
        $promotions = []; // Aquí cargarías promociones activas
        
        // Definir título de la página
        $pageTitle = "Inicio";
        
        // Cargar vista
        include __DIR__ . '/../../views/shop/home.php';
    }
}