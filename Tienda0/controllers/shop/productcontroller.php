<?php
class productcontroller {
    private $productModel;
    
    public function __construct() {
        require_once __DIR__ . '/../../models/product.php';
        $this->productModel = new product();
    }
    
    public function index() {
        // Obtener parámetros de paginación y filtros
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 12; // Productos por página
        $offset = ($page - 1) * $limit;
        
        // Aquí obtendrías los productos (implementar en model/product.php)
        // $result = $this->productModel->getAll($limit, $offset);
        // $products = $result['products'];
        // $totalPages = $result['pages'];
        
        // Datos de ejemplo mientras implementas el modelo
        $products = [];
        $totalPages = 1;
        
        $pageTitle = "Nuestros Productos";
        
        include __DIR__ . '/../../views/shop/product/list.php';
    }
    
    public function show($slug) {
        // Obtener producto por slug o ID
        // $product = $this->productModel->getBySlug($slug);
        
        // Datos de ejemplo
        $product = [
            'id' => 1,
            'name' => 'Producto de ejemplo',
            'description' => 'Este es un producto de ejemplo',
            'price' => 99.99
        ];
        
        if (!$product) {
            // Producto no encontrado
            $_SESSION['error_message'] = 'Producto no encontrado';
            redirect('/productos');
            return;
        }
        
        $pageTitle = $product['name'];
        
        include __DIR__ . '/../../views/shop/product/detail.php';
    }
    
    public function search() {
        $keyword = $_GET['q'] ?? '';
        
        // Implementar búsqueda en el modelo
        // $products = $this->productModel->search($keyword);
        
        // Datos de ejemplo
        $products = [];
        
        $pageTitle = "Resultados de búsqueda: " . htmlspecialchars($keyword);
        
        include __DIR__ . '/../../views/shop/product/search.php';
    }
}